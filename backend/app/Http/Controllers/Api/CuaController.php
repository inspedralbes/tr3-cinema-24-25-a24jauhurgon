<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CuaCompraVol;
use App\Models\ControlCompraVol;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

// Controlador de cua: entrar, consultar posició, sortir
class CuaController extends Controller
{
    /**
     * Inscriu un client a la cua virtual d'espera per a un vol específic.
     * Si hi ha capacitat al `ControlCompraVol`, l'autoritza immediatament; si no, el deixa "esperant".
     * Gestiona automàticament l'abandonament de cues prèvies si el client canvia de vol.
     * 
     * @param Request $request Dades del client (clientId).
     * @param int $volId Identificador del vol on es vol entrar a comprar.
     * @return \Illuminate\Http\JsonResponse Estat actualitzat de la cua ('esperant' o 'autoritzat').
     */
    public function entrar(Request $request, $volId)
    {
        $request->validate([
            'clientId' => 'required|string',
        ]);

        $clientId = $request->clientId;

        // Comprovar si ja està en alguna cua activa
        $cuaActiva = CuaCompraVol::where('clientId', $clientId)
            ->whereIn('estat', ['esperant', 'autoritzat'])
            ->first();

        if ($cuaActiva) {
            if ($cuaActiva->volId == $volId) {
                // Recuperar la sessió de cua existent pel mateix vol
                return response()->json([
                    'missatge' => $cuaActiva->estat === 'autoritzat' ? 'Autoritzat per comprar.' : 'Afegit a la cua.',
                    'cua' => $cuaActiva,
                ], 200);
            } else {
                // Abandonar la cua antiga si ha canviat de vol per no bloquejar l'usuari
                if ($cuaActiva->estat === 'autoritzat') {
                    $controlAntic = ControlCompraVol::where('volId', $cuaActiva->volId)->first();
                    if ($controlAntic && $controlAntic->actius > 0) {
                        $controlAntic->actius = $controlAntic->actius - 1;
                        $controlAntic->save();
                    }
                }
                $cuaActiva->estat = 'abandonat';
                $cuaActiva->save();
            }
        }

        // Comprovar si hi ha capacitat disponible
        $control = ControlCompraVol::where('volId', $volId)->first();

        if (!$control) {
            return response()->json(['missatge' => 'Control de compra no trobat per aquest vol.'], 404);
        }

        $estat = 'esperant';
        $ticket = null;
        $ticketExpiraAt = null;

        // Si hi ha espai, autoritzar directament
        if ($control->actius < $control->capacitat) {
            $estat = 'autoritzat';
            $ticket = Str::uuid()->toString();
            $ticketExpiraAt = Carbon::now()->addMinutes(2);
            $control->actius = $control->actius + 1;
            $control->save();
        }

        $entrada = CuaCompraVol::create([
            'volId' => $volId,
            'clientId' => $clientId,
            'estat' => $estat,
            'ticket' => $ticket,
            'ticketExpiraAt' => $ticketExpiraAt,
        ]);

        if ($estat === 'autoritzat') {
            $this->notificarAutoritzacio($volId, $clientId);
        }

        // Notificar a l'Admin Dashboard
        $this->notificarMonitoritzacio();

        return response()->json([
            'missatge' => $estat === 'autoritzat' ? 'Autoritzat per comprar.' : 'Afegit a la cua.',
            'cua' => $entrada,
        ], 201);
    }

    /**
     * Consulta l'estat actual i la posició númerica d'un client dins la cua d'espera.
     * La posició es calcula comptant quants usuaris 'esperant' han entrat abans que ell.
     * 
     * @param Request $request Dades del client (clientId).
     * @param int $volId Identificador del vol.
     * @return \Illuminate\Http\JsonResponse Dades de la cua incloent la 'posicio' i el 'ticket'.
     */
    public function posicio(Request $request, $volId)
    {
        $request->validate([
            'clientId' => 'required|string',
        ]);

        $entrada = CuaCompraVol::where('volId', $volId)
            ->where('clientId', $request->clientId)
            ->whereIn('estat', ['esperant', 'autoritzat'])
            ->first();

        if (!$entrada) {
            return response()->json(['missatge' => 'No estàs a la cua d\'aquest vol.'], 404);
        }

        $posicio = 0;
        if ($entrada->estat === 'autoritzat') {
            $this->notificarAutoritzacio($volId, $request->clientId);
            $posicio = 0;
        }

        // Notificar a l'Admin Dashboard
        $this->notificarMonitoritzacio();

        return response()->json([
            'estat' => $entrada->estat,
            'ticket' => $entrada->ticket,
            'ticketExpiraAt' => $entrada->ticketExpiraAt,
            'posicio' => $posicio,
        ]);
    }

    /**
     * Permet a un usuari abandonar voluntàriament la cua d'espera o el procés de compra.
     * Si l'usuari estava 'autoritzat', allibera immediatament un espai (`actius - 1`) perquè entri el següent.
     * 
     * @param Request $request Dades del client (clientId).
     * @param int $volId Identificador del vol.
     * @return \Illuminate\Http\JsonResponse Missatge de confirmació d'abandonament.
     */
    public function sortir(Request $request, $volId)
    {
        $request->validate([
            'clientId' => 'required|string',
        ]);

        $entrada = CuaCompraVol::where('volId', $volId)
            ->where('clientId', $request->clientId)
            ->whereIn('estat', ['esperant', 'autoritzat'])
            ->first();

        if (!$entrada) {
            return response()->json(['missatge' => 'No estàs a la cua.'], 404);
        }

        // Si estava autoritzat, alliberar espai
        if ($entrada->estat === 'autoritzat') {
            $control = ControlCompraVol::where('volId', $volId)->first();
            if ($control && $control->actius > 0) {
                $control->actius = $control->actius - 1;
                $control->save();
            }
        }

        $entrada->estat = 'abandonat';
        $entrada->save();

        // NotificarS a l'Admin Dashboard
        $this->notificarMonitoritzacio();

        // Processar la cua per deixar entrar el següent
        $this->processarCua($volId);

        return response()->json(['missatge' => 'Has sortit de la cua.']);
    }

    /**
     * Revisa la cua d'un vol i autoritza els següents usuaris si hi ha capacitat disponible.
     * Cridat quan algú surt o finalitza una compra, per automatitzar l'avenç sense esperar polling.
     */
    public function processarCua($volId)
    {
        $control = ControlCompraVol::where('volId', $volId)->first();
        if (!$control) return;

        // Quants espais lliures tenim?
        $lliures = $control->capacitat - $control->actius;

        if ($lliures > 0) {
            // Agafar els X primers que estiguin esperant
            $proxims = CuaCompraVol::where('volId', $volId)
                ->where('estat', 'esperant')
                ->orderBy('created_at', 'asc')
                ->limit($lliures)
                ->get();

            foreach ($proxims as $p) {
                $p->estat = 'autoritzat';
                $p->ticket = Str::uuid()->toString();
                $p->ticketExpiraAt = Carbon::now()->addMinutes(2);
                $p->save();

                $control->increment('actius');
                
                // Notificar directament a l'usuari
                $this->notificarAutoritzacio($volId, $p->clientId);
            }

            if ($proxims->count() > 0) {
                $this->notificarMonitoritzacio();
            }
        }
    }

    /**
     * Emet l'esdeveniment global 'monitoritzacio_actualitzada' per al Dashboard d'Administrador.
     * S'inboca ràpidament cada vegada que hi ha moviments d'entrada, sortida o avenç a la cua.
     * 
     * @return void
     */
    protected function notificarMonitoritzacio()
    {
        try {
            Http::timeout(0.2)->post('http://socket:3002/emit', [
                'event' => 'monitoritzacio_actualitzada',
                'payload' => []
            ]);
        } catch (\Exception $e) {}
    }

    /**
     * Notifica via WebSocket que l'usuari ha estat autoritzat per comprar.
     */
    protected function notificarAutoritzacio($volId, $clientId)
    {
        try {
            Http::timeout(0.2)->post('http://socket:3002/emit', [
                'event' => 'usuari_autoritzat',
                'payload' => [
                    'volId' => (int)$volId,
                    'clientId' => $clientId
                ]
            ]);
        } catch (\Exception $e) {}
    }
}
