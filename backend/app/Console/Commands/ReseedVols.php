<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Comanda per regenerar els vols de prova
// Esborra dades de vol anteriors i en crea de noves per avui + demà
// Execució: php artisan vols:reseed
class ReseedVols extends Command
{
    protected $signature = 'vols:reseed';
    protected $description = 'Regenera els vols de prova per avui i demà (neteja vols anteriors)';

    public function handle()
    {
        $this->info('Esborrant dades de vols anteriors...');

        DB::table('bitllets')->delete();
        DB::table('compres')->delete();
        DB::table('holdsSeients')->delete();
        DB::table('cuaCompraVol')->delete();
        DB::table('controlCompraVol')->delete();
        DB::table('volsInterns')->delete();
        DB::table('volsExternsCache')->delete();

        $this->info('Creant vols per avui i demà...');

        $avui = Carbon::today();
        $dema = Carbon::tomorrow();
        $ara = Carbon::now();

        $horaActual = intval(now()->format('H'));
        $volsAvui = [];
        $destins = ['MAD', 'MXP', 'LHR', 'CDG', 'AMS', 'FRA', 'FCO', 'ATH', 'BUD', 'CPH', 'IST', 'DUS', 'PMI', 'STN', 'ZRH', 'ORY'];
        $aerolínies = ['Vueling', 'Ryanair', 'British Airways', 'Air France', 'KLM', 'Lufthansa', 'Iberia', 'easyJet'];
        
        // Generar 12 vols per avui a partir d'ara
        for ($i = 0; $i < 12; $i++) {
            $h = ($horaActual + $i) % 24;
            $m = ($i % 2 == 0) ? '00' : '30';
            $volsAvui[] = [
                'ext' => "EXT-AUTO-$i",
                'desti' => $destins[$i % count($destins)],
                'num' => "AL-$i" . ($h*60 + intval($m)),
                'aero' => $aerolínies[$i % count($aerolínies)],
                'hora' => sprintf('%02d:%s', $h, $m),
                'model' => ($i % 4) + 1,
                'estat' => 'programat',
                'estatExt' => 'scheduled'
            ];
        }

        $volsDema = [
            ['ext' => 'EXT-T-VY1002', 'desti' => 'MAD', 'num' => 'VY1002', 'aero' => 'Vueling',           'hora' => '07:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-BA481',  'desti' => 'LHR', 'num' => 'BA481',  'aero' => 'British Airways',    'hora' => '08:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-AF1151', 'desti' => 'CDG', 'num' => 'AF1151', 'aero' => 'Air France',         'hora' => '09:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-KL1676', 'desti' => 'AMS', 'num' => 'KL1676', 'aero' => 'KLM',                'hora' => '09:30', 'model' => 4, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-FR9203', 'desti' => 'MXP', 'num' => 'FR9203', 'aero' => 'Ryanair',            'hora' => '10:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-IB3218', 'desti' => 'MAD', 'num' => 'IB3218', 'aero' => 'Iberia',             'hora' => '10:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-LH1139', 'desti' => 'FRA', 'num' => 'LH1139', 'aero' => 'Lufthansa',          'hora' => '11:00', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-TP1061', 'desti' => 'LIS', 'num' => 'TP1061', 'aero' => 'TAP Air Portugal',   'hora' => '11:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-TK1856', 'desti' => 'IST', 'num' => 'TK1856', 'aero' => 'Turkish Airlines',   'hora' => '12:00', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-SK1600', 'desti' => 'CPH', 'num' => 'SK1600', 'aero' => 'SAS',                'hora' => '13:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-VY6104', 'desti' => 'ATH', 'num' => 'VY6104', 'aero' => 'Vueling',            'hora' => '14:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
        ];

        $volsExterns = [];
        $volsInterns = [];

        $this->processarVols($volsAvui, $avui, $ara, $volsExterns, $volsInterns);
        $this->processarVols($volsDema, $dema, $ara, $volsExterns, $volsInterns);

        DB::table('volsExternsCache')->insert($volsExterns);
        DB::table('volsInterns')->insert($volsInterns);

        $volsInsertats = DB::table('volsInterns')->orderBy('id', 'asc')->get();
        for ($i = 0; $i < count($volsInsertats); $i++) {
            DB::table('controlCompraVol')->insert([
                'volId' => $volsInsertats[$i]->id,
                'actius' => 0,
                'capacitat' => 2,
                'created_at' => $ara,
                'updated_at' => $ara,
            ]);
        }

        $this->info('Creats ' . count($volsInterns) . ' vols (avui + demà).');
        $this->info('Tots amb ControlCompraVol creat.');

        return Command::SUCCESS;
    }

    private function processarVols($vols, $dia, $ara, &$volsExterns, &$volsInterns)
    {
        for ($i = 0; $i < count($vols); $i++) {
            $v = $vols[$i];
            $parts = explode(':', $v['hora']);
            $sortidaCarbon = $dia->copy()->setHour(intval($parts[0]))->setMinute(intval($parts[1]))->setSecond(0);
            $sortida = $sortidaCarbon->format('Y-m-d H:i:s');
            $sortidaReal = null;

            // Simulador d'arribades (Fase 10 & 12)
            $origensPeninsula = ['MAD', 'PMI', 'AGP', 'SVQ', 'BIO', 'VLC'];
            $volEntrantOrigen = $origensPeninsula[array_rand($origensPeninsula)];
            
            // L'avió arriba a BCN entre 30 minuts abans de la sortida i fins i tot podria portar retard
            // Creem un escenari aleatori.
            // Si l'hora d'arribada ja ha passat (respecte a $ara), l'estat és 'oberta'.
            // Si l'hora d'arribada és futura, l'estat és 'tancada'.
            
            // Fem que l'arribada sigui aproximadament entre ara mateix menys 1 hora i ara mateix més 1.5 hores
            // per assegurar-nos que tenim una bona barreja de vols oberts i vols tancats en compte enrere
            
            // Random offset in minutes between -60 and +90 from NOW
            $offsetMinutes = rand(-60, 90);
            $arribadaCarbon = $ara->copy()->addMinutes($offsetMinutes);
            $horaArribadaEsperada = $arribadaCarbon->format('Y-m-d H:i:s');
            
            $estatVenda = $arribadaCarbon->isPast() ? 'obert' : 'tancat';

            if ($v['estatExt'] === 'delayed') {
                $sortidaReal = $sortidaCarbon->copy()->addMinutes(20)->format('Y-m-d H:i:s');
            }

            $volsExterns[] = [
                'externalId' => $v['ext'],
                'origenIata' => 'BCN',
                'destiIata' => $v['desti'],
                'flightNumber' => $v['num'],
                'airline' => $v['aero'],
                'dataHoraSortidaEstimada' => $sortida,
                'dataHoraSortidaReal' => $sortidaReal,
                'estat' => $v['estatExt'],
                'rawJson' => null,
                'created_at' => $ara,
                'updated_at' => $ara,
            ];

            $volsInterns[] = [
                'externalId' => $v['ext'],
                'origenIata' => 'BCN',
                'destiIata' => $v['desti'],
                'dataHoraSortida' => $sortidaReal ? $sortidaReal : $sortida,
                'estat' => $v['estat'],
                'modelAvioId' => $v['model'],
                'capacitatCompra' => 2,
                'maximBitlletsPerCompra' => 4,
                'vol_entrant_origen' => $volEntrantOrigen,
                'hora_arribada_esperada' => $horaArribadaEsperada,
                'estat_venda' => $estatVenda,
                'created_at' => $ara,
                'updated_at' => $ara,
            ];
        }
    }
}
