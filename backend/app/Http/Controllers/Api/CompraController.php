<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HoldSeient;
use App\Models\Bitllet;
use App\Models\Compra;
use App\Models\VolIntern;
use App\Models\CuaCompraVol;
use App\Models\ControlCompraVol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\BitlletComprat;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

// Controlador de compra: holds, seatmap, confirmar compra
class CompraController extends Controller
{
    /**
     * Obtenir el mapa de seients d'un vol específic.
     * Calcula l'estat de cada seient (lliure, bloquejat, comprat) segons les taules HoldSeient i Bitllet.
     * Incorpora la lògica visual de First Class per a les primeres 3 files.
     * 
     * @param int $volId Identificador del vol intern.
     * @return \Illuminate\Http\JsonResponse Mapa de seients matricial i informació del model d'avió.
     */
    public function seatmap($volId)
    {
        $vol = VolIntern::with('modelAvio')->find($volId);

        if (!$vol || !$vol->modelAvio) {
            return response()->json(['missatge' => 'Vol o model d\'avió no trobat.'], 404);
        }

        $files = $vol->modelAvio->files;
        // Forcem 6 columnes sempre per a la demo de First Class, ignorant els avions petits (ex: Embraer)
        $columnes = 6; 
        $ara = Carbon::now();

        // Obtenir holds actius
        $holds = HoldSeient::where('volId', $volId)
            ->where('expiraAt', '>', $ara)
            ->get();

        // Obtenir seients comprats
        $comprats = Bitllet::where('volId', $volId)->get();

        // Construir mapa de seients
        $seatmap = [];
        for ($f = 1; $f <= $files; $f++) {
            $filaSeients = [];
            for ($c = 1; $c <= $columnes; $c++) {
                // Lògica First Class (Fase 15) - Primeres 3 files només tenen 4 seients
                // Saltem la columna 2 (B) i la 5 (E)
                if ($f <= 3 && ($c == 2 || $c == 5)) {
                    $filaSeients[] = [
                        'fila' => $f,
                        'columna' => $c,
                        'estat' => 'inexistent',
                    ];
                    continue; // Saltem la resta de comprovacions per a aquest seient "fantasma"
                }

                $estat = 'lliure';

                // Comprovar si està comprat
                for ($k = 0; $k < count($comprats); $k++) {
                    if ($comprats[$k]->fila === $f && $comprats[$k]->columna === $c) {
                        $estat = 'comprat';
                        break;
                    }
                }

                // Comprovar si està bloquejat (hold)
                if ($estat === 'lliure') {
                    for ($k = 0; $k < count($holds); $k++) {
                        if ($holds[$k]->fila === $f && $holds[$k]->columna === $c) {
                            $estat = 'bloquejat';
                            break;
                        }
                    }
                }

                $filaSeients[] = [
                    'fila' => $f,
                    'columna' => $c,
                    'estat' => $estat,
                ];
            }
            $seatmap[] = $filaSeients;
        }

        return response()->json([
            'seatmap' => $seatmap,
            'modelAvio' => $vol->modelAvio->nomModel,
            'files' => $files,
            'columnes' => $columnes,
        ]);
    }

    /**
     * Bloqueja temporalment un seient (crea o renova un Hold) durant 3 minuts.
     * Verifica l'autorització de l'usuari a la cua i que el seient estigui lliure abans d'actuar.
     * 
     * @param Request $request Dades enviades, inclou (clientId, fila, columna).
     * @param int $volId Identificador del vol intern on s'aplica el bloqueig.
     * @return \Illuminate\Http\JsonResponse Resultat de l'operació i detalls del hold creat.
     */
    public function bloquejarSeient(Request $request, $volId)
    {
        $request->validate([
            'clientId' => 'required|string',
            'fila' => 'required|integer|min:1',
            'columna' => 'required|integer|min:1',
        ]);

        // Validar que el client està autoritzat a la cua (exists és més ràpid que first)
        $autoritzat = CuaCompraVol::where('volId', $volId)
            ->where('clientId', $request->clientId)
            ->where('estat', 'autoritzat')
            ->exists();

        if (!$autoritzat) {
            return response()->json(['missatge' => 'No estàs autoritzat per seleccionar seients.'], 403);
        }

        // Comprovar disponibilitat (lliure de bitllet i de holds aliens)
        $comprat = Bitllet::where('volId', $volId)
            ->where('fila', $request->fila)
            ->where('columna', $request->columna)
            ->exists();

        if ($comprat) {
            return response()->json(['missatge' => 'Aquest seient ja està comprat.'], 409);
        }

        $ara = Carbon::now();
        $holdExistent = HoldSeient::where('volId', $volId)
            ->where('fila', $request->fila)
            ->where('columna', $request->columna)
            ->where('expiraAt', '>', $ara)
            ->first();

        if ($holdExistent && $holdExistent->clientId !== $request->clientId) {
            return response()->json(['missatge' => 'Seient bloquejat per un altre usuari.'], 409);
        }

        // Crear o renovar hold
        if ($holdExistent && $holdExistent->clientId === $request->clientId) {
            $holdExistent->expiraAt = Carbon::now()->addMinutes(3);
            $holdExistent->save();
            $hold = $holdExistent;
        } else {
            $hold = HoldSeient::create([
                'volId' => $volId,
                'clientId' => $request->clientId,
                'fila' => $request->fila,
                'columna' => $request->columna,
                'expiraAt' => Carbon::now()->addMinutes(3),
            ]);
        }

        // Notificar al Socket Server per a sincronització instantània del seatmap
        $this->notificarSocket('vol-' . $volId, 'seatmap-actualitzat', [
            'tipus' => 'bloquejat',
            'fila' => $request->fila,
            'columna' => $request->columna,
            'clientId' => $request->clientId
        ]);

        // Notificar actualització de mètriques per l'Admin Dashboard
        $this->notificarMonitoritzacio();

        return response()->json([
            'missatge' => 'Seient bloquejat correctament.',
            'hold' => $hold,
        ], 201);
    }

    /**
     * Allibera voluntàriament un seient prèviament bloquejat per l'usuari.
     * S'executa quan l'usuari desmarca un seient del Seatmap abans de comprar.
     * 
     * @param Request $request Dades del client i coordenades del seient.
     * @param int $volId Identificador del vol on es troba el seient.
     * @return \Illuminate\Http\JsonResponse Missatge de confirmació d'alliberament.
     */
    public function alliberarSeient(Request $request, $volId)
    {
        $request->validate([
            'clientId' => 'required|string',
            'fila' => 'required|integer|min:1',
            'columna' => 'required|integer|min:1',
        ]);

        $hold = HoldSeient::where('volId', $volId)
            ->where('clientId', $request->clientId)
            ->where('fila', $request->fila)
            ->where('columna', $request->columna)
            ->first();

        if (!$hold) {
            return response()->json(['missatge' => 'No tens cap hold en aquest seient.'], 404);
        }

        $hold->delete();

        // Notificar al Socket Server
        $this->notificarSocket('vol-' . $volId, 'seatmap-actualitzat', [
            'tipus' => 'alliberat',
            'fila' => $request->fila,
            'columna' => $request->columna
        ]);

        // Notificar actualització de mètriques per l'Admin Dashboard
        $this->notificarMonitoritzacio();

        return response()->json(['missatge' => 'Seient alliberat.']);
    }

    /**
     * Finalitza i processa la compra reial d'un conjunt de bitllets.
     * Valida la cua, comprova els límits de bitllets, assegura disponibilitat en transacció DB (evita dobles vendes),
     * genera els registres pertinents (Compra, Bitllet), descompta disponibilitat de l'avió i envia notificacions per e-mail i sockets.
     * 
     * @param Request $request Detalls de la compra (clientId, email, array de bitllets).
     * @param int $volId Identificador del vol on s'estan comprant els seients.
     * @return \Illuminate\Http\JsonResponse Detalls de la compra executada o errors de validació pertinents.
     */
    public function confirmar(Request $request, $volId)
    {
        $request->validate([
            'clientId' => 'required|string',
            'email' => 'required|email',
            'bitllets' => 'required|array|min:1',
            'bitllets.*.fila' => 'required|integer|min:1',
            'bitllets.*.columna' => 'required|integer|min:1',
            'bitllets.*.tarifaId' => 'required|integer|exists:tarifes,id',
            'bitllets.*.nomPassatger' => 'required|string|max:255',
        ]);

        $vol = VolIntern::find($volId);
        if (!$vol) {
            return response()->json([
                'error' => 'VOL_NO_TROBAT',
                'missatge' => 'El vol seleccionat ja no està disponible.'
            ], 404);
        }

        // Validar autorització a la cua
        $cuaEntrada = CuaCompraVol::where('volId', $volId)
            ->where('clientId', $request->clientId)
            ->where('estat', 'autoritzat')
            ->first();

        if (!$cuaEntrada) {
            return response()->json([
                'error' => 'NO_AUTORITZAT_CUA',
                'missatge' => 'La teva sessió a la cua ha caducat o no has estat autoritzat.'
            ], 403);
        }

        // Validar màxim bitllets
        if (count($request->bitllets) > $vol->maximBitlletsPerCompra) {
            return response()->json([
                'error' => 'MAXIM_BITLLETS_SUPERAT',
                'missatge' => 'Només pots comprar fins a ' . $vol->maximBitlletsPerCompra . ' bitllets per operació.',
            ], 422);
        }

        try {
            // Transacció per garantir atomicitat
            $compra = DB::transaction(function () use ($request, $volId, $cuaEntrada) {
                $total = 0;
                $bitlletsData = $request->bitllets;

                // Carregar tarifes (preu segur del servidor)
                $tarifaIds = [];
                for ($i = 0; $i < count($bitlletsData); $i++) {
                    $tarifaIds[] = $bitlletsData[$i]['tarifaId'];
                }
                $tarifesDB = \App\Models\Tarifa::whereIn('id', $tarifaIds)->get()->keyBy('id');

                for ($i = 0; $i < count($bitlletsData); $i++) {
                    $tarifa = $tarifesDB[$bitlletsData[$i]['tarifaId']];
                    $total = $total + floatval($tarifa->preu);
                }

                // Crear compra
                $compra = Compra::create([
                    'volId' => $volId,
                    'usuariId' => $request->user() ? $request->user()->id : null,
                    'email' => $request->email,
                    'total' => $total,
                ]);

                // Crear bitllets i verificar disponibilitat final
                for ($i = 0; $i < count($bitlletsData); $i++) {
                    $b = $bitlletsData[$i];
                    $tarifa = $tarifesDB[$b['tarifaId']];

                    // Comprovació crítica de doble compra (Race Condition protection)
                    $existent = Bitllet::where('volId', $volId)
                        ->where('fila', $b['fila'])
                        ->where('columna', $b['columna'])
                        ->lockForUpdate() // Bloqueja la fila fins final de transacció
                        ->first();

                    if ($existent) {
                        throw new \Exception('SEIENT_OCUPAT:' . $b['fila'] . chr(64 + $b['columna']));
                    }

                    Bitllet::create([
                        'compraId' => $compra->id,
                        'volId' => $volId,
                        'fila' => $b['fila'],
                        'columna' => $b['columna'],
                        'tipus' => $tarifa->nom,
                        'preu' => $tarifa->preu,
                        'nomPassatger' => $b['nomPassatger'],
                    ]);

                    // Eliminar hold
                    HoldSeient::where('volId', $volId)
                        ->where('fila', $b['fila'])
                        ->where('columna', $b['columna'])
                        ->delete();
                }

                // Finalitzar cua
                $cuaEntrada->update(['estat' => 'completat']);

                $control = ControlCompraVol::where('volId', $volId)->first();
                if ($control && $control->actius > 0) {
                    $control->decrement('actius');
                }

                // Processar la cua immediatament per deixar entrar el següent
                app(CuaController::class)->processarCua($volId);

                return $compra;
            });

            // Notificar Sockets que els seients ja no estan disponibles
            foreach ($request->bitllets as $b) {
                $this->notificarSocket('vol-' . $volId, 'seatmap-actualitzat', [
                    'tipus' => 'comprat',
                    'fila' => $b['fila'],
                    'columna' => $b['columna']
                ]);
            }

            // Enviar Email de confirmació (Fase 5)
            try {
                Mail::to($request->email)->send(new BitlletComprat($compra));
            } catch (\Exception $e) {
                // Si falla l'enviament de mail, registrem l'error però no tirem enrere la compra
                \Illuminate\Support\Facades\Log::error("Error enviant correu de bitllets: " . $e->getMessage());
            }

            // Notificar actualització de mètriques (seients comprats, cua completada)
            $this->notificarMonitoritzacio();

            return response()->json([
                'missatge' => 'Compra realitzada amb èxit!',
                'compra' => $compra->load('bitllets'),
            ], 201);

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            
            if (str_starts_with($msg, 'SEIENT_OCUPAT:')) {
                $seient = str_replace('SEIENT_OCUPAT:', '', $msg);
                return response()->json([
                    'error' => 'SEIENT_OCUPAT',
                    'missatge' => "El seient $seient s'acaba de vendre a un altre usuari."
                ], 409);
            }

            return response()->json([
                'error' => 'ERROR_INTERN',
                'missatge' => 'No s\'ha pogut completar la compra. Reintenta-ho.',
                'detall' => $msg
            ], 500);
        }
    }


    /**
     * Emet un esdeveniment genèric a través del servidor Socket.IO intern.
     * Facilita la comunicació asíncrona entre el backend (PHP) i el frontend (Vue).
     * 
     * @param string $room Nom de la sala Socket.IO (ex: 'vol-12'). Si és buit, és broadcast global.
     * @param string $event Nom identificador de l'esdeveniment (ex: 'seatmap-actualitzat').
     * @param array|object $payload Dades adjuntes a transmetre als clients.
     * @return void
     */
    protected function notificarSocket($room, $event, $payload)
    {
        try {
            \Illuminate\Support\Facades\Http::timeout(0.2)->post('http://socket:3002/emit', [
                'room' => $room,
                'event' => $event,
                'payload' => $payload
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error Socket emit: " . $e->getMessage());
        }
    }

    /**
     * Emet l'esdeveniment global 'monitoritzacio_actualitzada' al dashboard d'Administrador.
     * Cridat després d'una nova compra o alteració de cua per forçar el refresc de les estadístiques en temps real.
     * 
     * @return void
     */
    protected function notificarMonitoritzacio()
    {
        try {
            \Illuminate\Support\Facades\Http::timeout(0.2)->post('http://socket:3002/emit', [
                'event' => 'monitoritzacio_actualitzada',
                'payload' => []
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error Socket monitoritzacio: " . $e->getMessage());
        }
    }

    /**
     * Genera i retorna un fitxer PDF renderitzat amb els bitllets d'una compra específica.
     * Carrega les dades relacionades (Vol, Avió, Passatgers) i inclou codis QR validables per l'AdminScanner.
     * 
     * @param int $compraId Identificador de la compra de la qual generar el document.
     * @return \Illuminate\Http\Response Descarrega binària de l'arxiu PDF.
     */
    public function descarregarPdf($compraId)
    {
        $compra = Compra::with(['bitllets', 'volIntern.modelAvio'])->find($compraId);

        if (!$compra) {
            return response()->json(['missatge' => 'Compra no trobada.'], 404);
        }

        $vol = $compra->volIntern;
        $bitllets = $compra->bitllets;

        // Generar QR codes per a cada bitllet
        $qrCodes = [];
        for ($i = 0; $i < count($bitllets); $i++) {
            $b = $bitllets[$i];
            $contingutQr = 'last24bcn-R' . $compra->id
                . '-' . $vol->origenIata . $vol->destiIata
                . '-S' . $b->fila . chr(64 + $b->columna)
                . '-' . $b->nomPassatger;

            $opcions = new \chillerlan\QRCode\QROptions([
                'outputInterface' => \chillerlan\QRCode\Output\QRMarkupSVG::class,
            ]);
            $qr = new \chillerlan\QRCode\QRCode($opcions);
            $qrCodes[] = $qr->render($contingutQr);
        }

        $pdf = Pdf::loadView('bitllet-pdf', [
            'compra' => $compra,
            'vol' => $vol,
            'bitllets' => $bitllets,
            'qrCodes' => $qrCodes,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $nomFitxer = 'last24bcn-Reserva-' . $compra->id . '.pdf';
        return $pdf->download($nomFitxer);
    }
}
