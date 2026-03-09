<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VolIntern;
use App\Models\Tarifa;
use Illuminate\Http\Request;

// Controlador de vols: llistat i detall
class VolsController extends Controller
{
    /**
     * Retorna el llistat públic de vols 'last-minute' que surten de Barcelona en una finestra de temps fixada (ex: pròximes 24h).
     * S'encarrega d'invocar els processos de neteja i càrrega peresa (Lazy Loading) abans de retornar dades fresques.
     * Només mostra vols amb 'estat_venda=obert' i amaga aquells cancel·lats o tancats (pròxima arribada).
     * 
     * @param Request $request Filtres opcionals URL (finestraMinuts, desti).
     * @return \Illuminate\Http\JsonResponse Dades resumides de cada vol preparades per a la cartellera.
     */
    public function llistat(Request $request)
    {
        // 1. Garantir que tenim vols per avui (Lazy Loading)
        $this->garantirVolsAvui();

        // 2. Netejar vols passats sense ventes
        $this->netejarVolsPassats();

        $finestraMinuts = $request->query('finestraMinuts', 1440);
        $desti = $request->query('desti', null);
        $cutoffMinuts = 0; // DEBUG: Treiem el cutoff de 3h per veure si surten vols
        $ara = now();
        $minim = now()->addMinutes($cutoffMinuts);
        $limit = now()->addMinutes(intval($finestraMinuts));

        $query = VolIntern::where('origenIata', 'BCN')
            ->where('dataHoraSortida', '>=', now()) // Canviat de $minim a now()
            ->where('dataHoraSortida', '<=', $limit)
            ->with(['modelAvio', 'controlCompra']);

        if ($desti) {
            $query->where('destiIata', $desti);
        }

        $vols = $query->orderBy('dataHoraSortida', 'asc')->get();
        $estatsPermesos = ['programat', 'retardat'];

        $resultat = [];
        for ($i = 0; $i < count($vols); $i++) {
            $vol = $vols[$i];
            $teControl = $vol->controlCompra !== null;
            $estatValid = false;
            for ($j = 0; $j < count($estatsPermesos); $j++) {
                if ($vol->estat === $estatsPermesos[$j]) {
                    $estatValid = true;
                    break;
                }
            }
            $disponible = $teControl && $estatValid && ($vol->estat_venda === 'obert');

            $motiu = null;
            if (!$disponible) {
                if (!$teControl) {
                    $motiu = 'Compra no habilitada per aquest vol';
                } else if ($vol->estat === 'cancel·lat' || $vol->estat === 'cancelat') {
                    $motiu = 'Vol cancel·lat';
                } else if ($vol->estat_venda === 'tancat') {
                    $motiu = 'Venda no oberta (Esperant vol entrant)';
                } else if ($vol->estat_venda === 'finalitzat') {
                    $motiu = 'Venda tancada';
                } else {
                    $motiu = 'Vol en estat: ' . $vol->estat;
                }
            }

            $resultat[] = [
                'id' => $vol->id,
                'origenIata' => $vol->origenIata,
                'destiIata' => $vol->destiIata,
                'vol_entrant_origen' => $vol->vol_entrant_origen,
                'hora_arribada_esperada' => $vol->hora_arribada_esperada,
                'estat_venda' => $vol->estat_venda,
                'dataHoraSortida' => $vol->dataHoraSortida,
                'estat' => $vol->estat,
                'modelAvio' => $vol->modelAvio ? $vol->modelAvio->nomModel : null,
                'maximBitlletsPerCompra' => $vol->maximBitlletsPerCompra,
                'externalId' => $vol->externalId,
                'disponiblePerCompra' => $disponible,
                'motiuNoDisponible' => $motiu,
            ];
        }

        return response()->json(['vols' => $resultat]);
    }

    /**
     * Retorna dades ofuscades de tots els vols on l'hora de sortida ja ha passat.
     * S'utilitza exclusivament com a arxiu/registre històric.
     * Per raons de privacitat no retorna PII (informació personal), només posicions de seients generals ocupats.
     * 
     * @param Request $request Petició sense paràmetres obligatoris.
     * @return \Illuminate\Http\JsonResponse Llistat de vols passats preparats per visualització en mapa de punts.
     */
    public function historial(Request $request)
    {
        // Netejar abans de mostrar
        $this->netejarVolsPassats();

        $vols = VolIntern::where('origenIata', 'BCN')
            ->where('dataHoraSortida', '<', now())
            ->with(['modelAvio', 'bitllets'])
            ->orderBy('dataHoraSortida', 'desc')
            ->get();

        $resultat = [];
        for ($i = 0; $i < count($vols); $i++) {
            $vol = $vols[$i];
            
            // Dades de seients (Privacitat: NOMÉS fila i columna)
            $seientsOcupats = [];
            for ($j = 0; $j < count($vol->bitllets); $j++) {
                $b = $vol->bitllets[$j];
                $seientsOcupats[] = [
                    'f' => $b->fila,
                    'c' => $b->columna
                ];
            }

            $resultat[] = [
                'id' => $vol->id,
                'origenIata' => $vol->origenIata,
                'destiIata' => $vol->destiIata,
                'dataHoraSortida' => $vol->dataHoraSortida,
                'estat' => $vol->estat,
                'externalId' => $vol->externalId,
                'modelAvio' => $vol->modelAvio ? [
                    'nom' => $vol->modelAvio->nomModel,
                    'files' => $vol->modelAvio->files,
                    'columnes' => $vol->modelAvio->columnes,
                    'seientsTotals' => $vol->modelAvio->seientsTotals,
                ] : null,
                'bitlletsComprats' => count($vol->bitllets),
                'seientsOcupats' => $seientsOcupats, // Llista simplificada per al seatmap
            ];
        }

        return response()->json(['vols' => $resultat]);
    }

    /**
     * Comprova si hi ha vols programats per a les properes 24 hores.
     * Si no n'hi ha cap (Base de Dades buida en reiniciar), executa una sembra (`vols:reseed`) 
     * simulant una connexió amb l'API pública per captar nous vols operatius (Lazy Loading).
     * 
     * @return void
     */
    protected function garantirVolsAvui()
    {
        // Mirar si hi ha vols programats per les properes 24h
        $hiHaVols = VolIntern::where('dataHoraSortida', '>', now())
            ->where('dataHoraSortida', '<', now()->addHours(24))
            ->exists();

        if (!$hiHaVols) {
            // Si no hi ha vols, "simulem" la crida a l'API externa regenerant dades
            // Nota: En un sistema real, aquí cridaríem a un Service d'API externa.
            \Illuminate\Support\Facades\Artisan::call('vols:reseed');
            \Illuminate\Support\Facades\Log::info("Lazy Loading: Nous vols carregats automàticament amb vols:reseed.");
        }
    }

    /**
     * Funcionalitat de recollida de deixalles (Garbage Collection / Cleanup).
     * Cerca tots els vols on la data de sortida ja ha passat i, si no han venut absolutament cap bitllet,
     * esborra definitivament el vol per evitar omplir la DB amb brossa innecessària.
     * Es deixen vius aquells vols amb vendes confirmades per a l'Historial de consulta.
     * 
     * @return void
     */
    protected function netejarVolsPassats()
    {
        // Buscar vols que ja han sortit amb el recompte de bitllets
        $volsPassats = VolIntern::where('dataHoraSortida', '<', now())
            ->withCount('bitllets')
            ->get();

        $idsAEsborrar = [];
        for ($i = 0; $i < count($volsPassats); $i++) {
            if ($volsPassats[$i]->bitllets_count === 0) {
                $idsAEsborrar[] = $volsPassats[$i]->id;
            }
        }

        if (count($idsAEsborrar) > 0) {
            \App\Models\ControlCompraVol::whereIn('volId', $idsAEsborrar)->delete();
            \App\Models\CuaCompraVol::whereIn('volId', $idsAEsborrar)->delete();
            \App\Models\HoldSeient::whereIn('volId', $idsAEsborrar)->delete();
            VolIntern::whereIn('id', $idsAEsborrar)->delete();
            
            \Illuminate\Support\Facades\Log::info("Cleanup: Esborrats " . count($idsAEsborrar) . " vols passats sense vendes.");
        }
    }

    /**
     * Consulta els detalls exhaustius i l'estat en directe d'un vol específic seleccionat per l'usuari.
     * Aquesta funció es crida per alimentar la finestra de cua i check-out.
     * 
     * @param int $id Identificador del vol intern.
     * @return \Illuminate\Http\JsonResponse Tota la informació requerida del vol o els motius si aquest no està disponible.
     */
    public function detall($id)
    {
        $vol = VolIntern::with(['modelAvio', 'controlCompra'])->find($id);

        if (!$vol) {
            return response()->json(['missatge' => 'Vol no trobat.'], 404);
        }

        $teControl = $vol->controlCompra !== null;
        $estatsPermesos = ['programat', 'retardat'];
        $estatValid = false;
        for ($j = 0; $j < count($estatsPermesos); $j++) {
            if ($vol->estat === $estatsPermesos[$j]) {
                $estatValid = true;
                break;
            }
        }
        $disponible = $teControl && $estatValid && ($vol->estat_venda === 'obert');

        $motiu = null;
        if (!$disponible) {
            if (!$teControl) {
                $motiu = 'Compra no habilitada per aquest vol';
            } else if ($vol->estat === 'cancel·lat' || $vol->estat === 'cancelat') {
                $motiu = 'Vol cancel·lat';
            } else if ($vol->estat_venda === 'tancat') {
                $motiu = 'Venda no oberta (Esperant vol entrant)';
            } else if ($vol->estat_venda === 'finalitzat') {
                $motiu = 'Venda tancada';
            } else {
                $motiu = 'Vol en estat: ' . $vol->estat;
            }
        }

        return response()->json([
            'vol' => [
                'id' => $vol->id,
                'externalId' => $vol->externalId,
                'origenIata' => $vol->origenIata,
                'destiIata' => $vol->destiIata,
                'vol_entrant_origen' => $vol->vol_entrant_origen,
                'hora_arribada_esperada' => $vol->hora_arribada_esperada,
                'estat_venda' => $vol->estat_venda,
                'dataHoraSortida' => $vol->dataHoraSortida,
                'estat' => $vol->estat,
                'modelAvio' => $vol->modelAvio,
                'capacitatCompra' => $vol->capacitatCompra,
                'maximBitlletsPerCompra' => $vol->maximBitlletsPerCompra,
                'disponiblePerCompra' => $disponible,
                'motiuNoDisponible' => $motiu,
            ],
        ]);
    }

    /**
     * Llista el catàleg mestre de tarifes actives (Ex: Regular, Premium, First Class).
     * Aquestes dades defineixen els preus creuats que s'oferiran al llistat final durant la selecció del passatger.
     * 
     * @return \Illuminate\Http\JsonResponse Conjunt de tarifes de compra per defecte.
     */
    public function tarifes()
    {
        $tarifes = Tarifa::where('activa', true)->get();

        $resultat = [];
        for ($i = 0; $i < count($tarifes); $i++) {
            $resultat[] = [
                'id' => $tarifes[$i]->id,
                'nom' => $tarifes[$i]->nom,
                'preu' => $tarifes[$i]->preu,
                'descripcio' => $tarifes[$i]->descripcio,
            ];
        }

        return response()->json(['tarifes' => $resultat]);
    }
}
