<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModelAvio;
use App\Models\VolIntern;
use App\Models\ControlCompraVol;
use App\Models\CuaCompraVol;
use App\Models\HoldSeient;
use App\Models\Bitllet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Controlador d'administració: CRUD de vols i avions, monitorització
class AdminController extends Controller
{
    /**
     * Retorna un llistat complet de tots els Models d'Avió registrats a la BD.
     * S'utilitza al desplegable de creació de vols de l'Admin Dashboard.
     * 
     * @return \Illuminate\Http\JsonResponse Llista JSON amb els models i les seves capacitats.
     */
    public function llistatModelsAvio()
    {
        return response()->json(['models' => ModelAvio::all()]);
    }

    /**
     * Crea i desa un nou Model d'Avió a la base de dades.
     * Calcula automàticament els seients totals multiplicant files per columnes.
     * 
     * @param Request $request Dades del model (nomModel, files, columnes, descripcio opcional).
     * @return \Illuminate\Http\JsonResponse Dades del model creat o errors de validació.
     */
    public function crearModelAvio(Request $request)
    {
        $request->validate([
            'nomModel' => 'required|string|max:100',
            'files' => 'required|integer|min:1',
            'columnes' => 'required|integer|min:1',
            'descripcio' => 'nullable|string',
        ]);

        $model = ModelAvio::create([
            'nomModel' => $request->nomModel,
            'files' => $request->files,
            'columnes' => $request->columnes,
            'seientsTotals' => $request->files * $request->columnes,
            'descripcio' => $request->descripcio,
        ]);

        return response()->json(['model' => $model], 201);
    }

    /**
     * Actualitza les característiques d'un Model d'Avió existent.
     * Si es modifiquen les files o columnes, recalcula la capacitat total (`seientsTotals`).
     * 
     * @param Request $request Dades a modificar (fields parcials `sometimes`).
     * @param int $id Identificador del model d'avió.
     * @return \Illuminate\Http\JsonResponse Resultat de l'actualització.
     */
    public function actualitzarModelAvio(Request $request, $id)
    {
        $model = ModelAvio::find($id);
        if (!$model) {
            return response()->json(['missatge' => 'Model no trobat.'], 404);
        }

        $request->validate([
            'nomModel' => 'sometimes|string|max:100',
            'files' => 'sometimes|integer|min:1',
            'columnes' => 'sometimes|integer|min:1',
            'descripcio' => 'nullable|string',
        ]);

        if ($request->has('nomModel')) { $model->nomModel = $request->nomModel; }
        if ($request->has('files')) { $model->files = $request->files; }
        if ($request->has('columnes')) { $model->columnes = $request->columnes; }
        if ($request->has('descripcio')) { $model->descripcio = $request->descripcio; }

        if ($request->has('files') || $request->has('columnes')) {
            $model->seientsTotals = $model->files * $model->columnes;
        }

        $model->save();

        return response()->json(['model' => $model]);
    }

    /**
     * Esborra un Model d'Avió de la base de dades.
     * Vigilar: Fallarà per integritat referencial si el model ja està associat a un Vol (foreign key).
     * 
     * @param int $id Identificador del model.
     * @return \Illuminate\Http\JsonResponse Missatge d'èxit o error 404 si no existeix.
     */
    public function eliminarModelAvio($id)
    {
        $model = ModelAvio::find($id);
        if (!$model) {
            return response()->json(['missatge' => 'Model no trobat.'], 404);
        }

        $model->delete();
        return response()->json(['missatge' => 'Model eliminat.']);
    }

    /**
     * Recupera i llista tots els vols interns programats, incloent dades del seu Avió associat i del Control de Compra.
     * Dades per popular la taula principal de l'Admin Dashboard.
     * 
     * @return \Illuminate\Http\JsonResponse Conjunt de vols interns disponibles per administrar.
     */
    public function llistatVolsInterns()
    {
        $vols = VolIntern::with('modelAvio', 'controlCompra')->get();
        return response()->json(['vols' => $vols]);
    }

    /**
     * Crea un nou vol intern amb origen fix ('BCN') i un destí escollit.
     * Al mateix temps, auto-genera un registre `ControlCompraVol` associat en blanc per gestionar les cues d'aquest vol.
     * 
     * @param Request $request Dades de programació (destiIata, dataHoraSortida, modelAvioId...).
     * @return \Illuminate\Http\JsonResponse Dades del Vol acabat de dissenyar incloent la relació de l'avió.
     */
    public function crearVolIntern(Request $request)
    {
        $request->validate([
            'destiIata' => 'required|string|size:3',
            'dataHoraSortida' => 'required|date',
            'modelAvioId' => 'required|exists:modelsAvio,id',
            'externalId' => 'nullable|string',
            'maximBitlletsPerCompra' => 'sometimes|integer|min:1|max:10',
        ]);

        $vol = VolIntern::create([
            'origenIata' => 'BCN',
            'destiIata' => strtoupper($request->destiIata),
            'dataHoraSortida' => $request->dataHoraSortida,
            'estat' => 'programat',
            'modelAvioId' => $request->modelAvioId,
            'externalId' => $request->externalId,
            'capacitatCompra' => 10,
            'maximBitlletsPerCompra' => $request->input('maximBitlletsPerCompra', 4),
        ]);

        // Crear control de compra
        ControlCompraVol::create([
            'volId' => $vol->id,
            'actius' => 0,
            'capacitat' => 10,
        ]);

        return response()->json(['vol' => $vol->load('modelAvio')], 201);
    }

    /**
     * Actualitza les dades d'un vol programat abans de la seva sortida.
     * Permet canviar el destí, data/hora, avió assignat i paràmetres com bitllets per compra.
     * 
     * @param Request $request Dades parcials actualitzades del vol.
     * @param int $id ID del vol específic a tractar.
     * @return \Illuminate\Http\JsonResponse Resultats amb el vol modificat.
     */
    public function actualitzarVolIntern(Request $request, $id)
    {
        $vol = VolIntern::find($id);
        if (!$vol) {
            return response()->json(['missatge' => 'Vol no trobat.'], 404);
        }

        $request->validate([
            'destiIata' => 'sometimes|string|size:3',
            'dataHoraSortida' => 'sometimes|date',
            'modelAvioId' => 'sometimes|exists:modelsAvio,id',
            'estat' => 'sometimes|string',
            'maximBitlletsPerCompra' => 'sometimes|integer|min:1|max:10',
        ]);

        if ($request->has('destiIata')) { $vol->destiIata = strtoupper($request->destiIata); }
        if ($request->has('dataHoraSortida')) { $vol->dataHoraSortida = $request->dataHoraSortida; }
        if ($request->has('modelAvioId')) { $vol->modelAvioId = $request->modelAvioId; }
        if ($request->has('estat')) { $vol->estat = $request->estat; }
        if ($request->has('maximBitlletsPerCompra')) { $vol->maximBitlletsPerCompra = $request->maximBitlletsPerCompra; }

        $vol->save();

        return response()->json(['vol' => $vol->load('modelAvio')]);
    }

    /**
     * Elimina completament un vol intern programat.
     * Si ja té bitllets venuts associats, aquesta operació fallarà per restriccions de Clau Forana (Foreign Key constraint).
     * 
     * @param int $id ID del vol a cancel·lar de la base de dades.
     * @return \Illuminate\Http\JsonResponse Resposta de confirmació o error.
     */
    public function eliminarVolIntern($id)
    {
        $vol = VolIntern::find($id);
        if (!$vol) {
            return response()->json(['missatge' => 'Vol no trobat.'], 404);
        }

        $vol->delete();
        return response()->json(['missatge' => 'Vol eliminat.']);
    }

    /**
     * Força manualment el canvi d'estat de venda d'un vol ('obert', 'tancat', 'finalitzat').
     * Aquesta funció emet events en temps real cap als Websockets (`vol_estat_actualitzat` i `monitoritzacio_actualitzada`)
     * perquè la UI dels compradors i de l'Admin es refresqui a l'instant, amagant o mostrant el botó de Comprar i actualitzant panells.
     * 
     * @param Request $request Conté el nou `estat_venda` desitjat.
     * @param int $id ID del vol a forçar.
     * @return \Illuminate\Http\JsonResponse Nou estat aplicat al vol.
     */
    public function forceStatus(Request $request, $id)
    {
        $vol = VolIntern::find($id);
        if (!$vol) {
            return response()->json(['missatge' => 'Vol no trobat.'], 404);
        }

        $request->validate([
            'estat_venda' => 'required|string|in:tancat,obert,finalitzat',
        ]);

        $vol->estat_venda = $request->estat_venda;
        $vol->save();

        if ($vol->estat_venda === 'obert') {
            app(CuaController::class)->processarCua($id);
        }

        // Emetre event Socket.IO per a la llista de vols pública i Dashboard
        try {
            Http::timeout(0.2)->post('http://socket:3002/emit', [
                'event' => 'vol_estat_actualitzat',
                'payload' => [
                    'volId' => $vol->id,
                    'nou_estat' => $vol->estat_venda
                ]
            ]);
        } catch (\Exception $e) {
            \Log::warning("No s'ha pogut emetre 'vol_estat_actualitzat': " . $e->getMessage());
        }

        // També la monitorització general ha canviat per al Dashboard
        try {
            Http::timeout(0.2)->post('http://socket:3002/emit', [
                'event' => 'monitoritzacio_actualitzada',
                'payload' => []
            ]);
        } catch (\Exception $e) {}

        return response()->json(['missatge' => 'Estat de venda actualitzat.', 'estat_venda' => $vol->estat_venda]);
    }

    /**
     * Comprova la validesa d'un codi QR d'embarcament escanetjat via càmera.
     * Valida el bitllet existent a partir del text llegit i prevé la doble-lectura comprovant la `hora_embarcament`.
     * Si la persona pot entrar, registra l'hora exacta i puja la barra de progrés enviant un ping via Socket.IO al Dashboard.
     * 
     * @param Request $request Text cru decodificat de la imatge QR.
     * @return \Illuminate\Http\JsonResponse Dades de rebuig (409 Conflict / 404 Invalid) o èxit (200 OK).
     */
    public function checkinQR(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        $qrCode = $request->qr_code;
        // Format of QR: last24bcn-R{compraId}-{origen}{desti}-S{fila}{lletra_columna}-{nom}
        // Example: last24bcn-R9-LHRBCN-S1A-Anna
        
        // We can parse the QR to find the exact ticket
        // Or simpler, search by finding the row/col and compraId
        // Let's parse it:
        if (!preg_match('/last24bcn-R(\d+)-([A-Z]{6})-S(\d+)([A-Z])-(.*)/', $qrCode, $matches)) {
            return response()->json(['error' => 'FORMAT_INVALID', 'missatge' => 'Codi QR invàlid o de format desconegut.'], 400);
        }

        $compraId = $matches[1];
        $fila = $matches[3];
        $lletraColumna = $matches[4]; // A=1, B=2, C=3, D=4, E=5, F=6
        $columnaInt = ord($lletraColumna) - 64; // Convert 'A' to 1

        $bitllet = Bitllet::where('compraId', $compraId)
            ->where('fila', $fila)
            ->where('columna', $columnaInt)
            ->first();

        if (!$bitllet) {
            return response()->json(['error' => 'NO_TROBAT', 'missatge' => 'El bitllet no existeix.'], 404);
        }

        if ($bitllet->hora_embarcament !== null) {
            return response()->json([
                'error' => 'JA_EMBARCAT',
                'missatge' => 'Aquest bitllet ja ha estat escanejat prèviament a les ' . \Carbon\Carbon::parse($bitllet->hora_embarcament)->format('H:i:s')
            ], 409);
        }

        // Embarcar el passatger
        $bitllet->hora_embarcament = now();
        $bitllet->save();

        // Notificar al Dashboard que la barra ha de pujar
        try {
            Http::timeout(1)->post('http://socket:3002/emit', [
                'event' => 'barreta_embarcament_actualitzada',
                'payload' => [
                    'volId' => $bitllet->volId,
                    'compraId' => $bitllet->compraId,
                    'fila' => $bitllet->fila,
                    'columna' => $bitllet->columna
                ]
            ]);
        } catch (\Exception $e) {}

        // També notificar monitorització general per refrescar estadístiques
        try {
            Http::timeout(1)->post('http://socket:3002/emit', [
                'event' => 'monitoritzacio_actualitzada',
                'payload' => []
            ]);
        } catch (\Exception $e) {}

        return response()->json([
            'missatge' => 'Passatger embarcat correctament.',
            'bitllet' => $bitllet
        ], 200);
    }

    // --- Monitorització ---

    /**
     * Retorna una instantània global (Snapshot) de l'estat en viu del sistema per a tots els vols actius.
     * Consolida mètriques com: persones a la cua, seients en cistella, entrades venudes i passatgers embarcats (QR).
     * Nodreix les gràfiques i comptadors de l'Admin Dashboard principal.
     * 
     * @return \Illuminate\Http\JsonResponse Llista completament calculada de les estadístiques per vol.
     */
    public function monitoritzacio()
    {
        // Usem withCount per obtenir totes les mètriques en una sola consulta SQL (sense N+1)
        $vols = VolIntern::with('controlCompra')
            ->withCount([
                'cuaEntrades as cuaEsperant' => function($q) { 
                    $q->where('estat', 'esperant'); 
                },
                'cuaEntrades as cuaAutoritzats' => function($q) { 
                    $q->where('estat', 'autoritzat'); 
                },
                'holdsSeients as holdsActius' => function($q) { 
                    $q->where('expiraAt', '>', now()); 
                },
                'bitllets as seientsComprats',
                'bitllets as seientsEmbarcats' => function($q) { 
                    $q->whereNotNull('hora_embarcament'); 
                }
            ])
            ->get();

        $dades = $vols->map(function ($vol) {
            return [
                'volId' => $vol->id,
                'destiIata' => $vol->destiIata,
                'dataHoraSortida' => $vol->dataHoraSortida,
                'estat' => $vol->estat,
                'estat_venda' => $vol->estat_venda,
                'vol_entrant_origen' => $vol->vol_entrant_origen,
                'hora_arribada_esperada' => $vol->hora_arribada_esperada,
                'cuaEsperant' => $vol->cua_esperant_count ?? 0,
                'cuaAutoritzats' => $vol->cua_autoritzats_count ?? 0,
                'holdsActius' => $vol->holds_actius_count ?? 0,
                'seientsComprats' => $vol->seients_comprats_count ?? 0,
                'seientsEmbarcats' => $vol->seients_embarcats_count ?? 0,
                'capacitatActius' => $vol->controlCompra ? $vol->controlCompra->actius : 0,
            ];
        });

        return response()->json(['monitoritzacio' => $dades]);
    }

    // --- Gestió d'Usuaris ---

    /**
     * Retorna el registre complet d'usuaris inscrits a la plataforma ordenats pel més recent.
     * S'utilitza exclusivament al panell "Gestió d'Usuaris" de l'Admin per auditar i gestionar rols.
     * Inclou bandera `google_id` per saber si s'han registrat via perfil social.
     * 
     * @return \Illuminate\Http\JsonResponse Dades dels usuaris (id, name, email, rol, etc.).
     */
    public function llistatUsuaris()
    {
        $usuaris = User::select('id', 'name', 'email', 'rol', 'created_at', 'google_id')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['usuaris' => $usuaris]);
    }

    /**
     * Alterna (Toggle) el rol d'un usuari regular entre 'general' i 'premium'.
     * Emet instantàniament via Socket.IO el canvi (`rol_actualitzat`) perquè la UI de l'usuari final 
     * desbloquegi/bloquegi funcions Premium sense necessitat que aquest refresqui la pàgina o torni a iniciar sessió.
     * Bloqueja la modificació de comptes d'altres administradors per seguretat.
     * 
     * @param int $id Identificador intern de l'Usuari.
     * @return \Illuminate\Http\JsonResponse Resultat de l'operació (400 si l'usuari objectiu ja és admin).
     */
    public function toggleRolUsuari($id)
    {
        $usuari = User::find($id);
        if (!$usuari) {
            return response()->json(['missatge' => 'Usuari no trobat.'], 404);
        }

        if ($usuari->esAdmin()) {
            return response()->json(['missatge' => 'No pots modificar el rol d\'un administrador.'], 400);
        }

        // Alternar entre general i premium
        $usuari->rol = $usuari->rol === 'premium' ? 'general' : 'premium';
        $usuari->save();

        // Emetre event Socket.IO per a temps real
        try {
            Http::timeout(2)->post('http://socket:3002/emit', [
                'event' => 'rol_actualitzat',
                'payload' => [
                    'usuari_id' => $usuari->id,
                    'nou_rol' => $usuari->rol
                ]
            ]);
        } catch (\Exception $e) {
            // Falla de forma silenciosa si el socket no està disponible
            \Log::warning("No s'ha pogut emetre 'rol_actualitzat' cap al Socket.IO");
        }

        return response()->json([
            'missatge' => 'Rol d\'usuari actualitzat.',
            'usuari' => [
                'id' => $usuari->id,
                'rol' => $usuari->rol
            ]
        ]);
    }

    // --- Live Seatmap ---

    /**
     * Extreu les identitats (noms de PII) d'aquells seients i holds actius en un determinat vol.
     * A diferència de l'API normal per a compradors (que és cega/anònima respecte la identitat i només retorna estatus), 
     * aquesta inclou manualment el nom de qui l'ha comprat i el moment exacte que han fet `hora_embarcament`
     * per recolzar el detector visual "Live Seatmap" de la torre de control administrativa.
     * 
     * @param int $id Identificador de Vol.
     * @return \Illuminate\Http\JsonResponse Llistats `seientsComprats` i `seientsBloquejats` nodrits amb un camp abstracte 'usuari.name'.
     */
    public function seients($id)
    {
        $vol = VolIntern::find($id);
        if (!$vol) {
            return response()->json(['missatge' => 'Vol no trobat.'], 404);
        }

        // Seients confirmats i usuaris que els han comprat
        $bitlletsRaw = Bitllet::where('volId', $id)->with('compra.usuari')->get();
        $bitllets = $bitlletsRaw->map(function ($b) {
            $nomUsuari = 'Desconegut';
            if ($b->compra && $b->compra->usuari) {
                $nomUsuari = $b->compra->usuari->name;
            } elseif ($b->nomPassatger) {
                $nomUsuari = $b->nomPassatger;
            }

            return [
                'fila' => $b->fila,
                'columna' => $b->columna,
                'hora_embarcament' => $b->hora_embarcament,
                'usuari' => ['name' => $nomUsuari]
            ];
        });

        // Seients bloquejats temporalment (només tenim clientId a aquesta fase)
        $holdsRaw = HoldSeient::where('volId', $id)
            ->where('expiraAt', '>', now())
            ->get();
            
        $holds = $holdsRaw->map(function ($h) {
            return [
                'fila' => $h->fila,
                'columna' => $h->columna,
                'usuari' => ['name' => 'En Cistella']
            ];
        });

        return response()->json([
            'seientsComprats' => $bitllets,
            'seientsBloquejats' => $holds
        ]);
    }
}
