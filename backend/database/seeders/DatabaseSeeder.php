<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

// Seeder principal: crea totes les dades inicials
// Vols d'avui (07:00-19:30) + demà (07:00-14:00)
// Finestra de compra: 3h cutoff, 24h finestra
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->crearUsuaris();
        $this->crearModelsAvio();
        $this->crearTarifes();
        $this->crearVolsExemple();
    }

    private function crearUsuaris()
    {
        $ara = Carbon::now();
        DB::table('users')->insert([
            [
                'name' => 'Administrador',
                'email' => 'admin@ultimahorabcn.cat',
                'password' => Hash::make('password'),
                'rol' => 'admin',
                'email_verified_at' => $ara,
                'created_at' => $ara,
                'updated_at' => $ara,
            ],
            [
                'name' => 'Joan Premium',
                'email' => 'premium@example.com',
                'password' => Hash::make('password'),
                'rol' => 'premium',
                'email_verified_at' => $ara,
                'created_at' => $ara,
                'updated_at' => $ara,
            ],
            [
                'name' => 'Anna General',
                'email' => 'general@example.com',
                'password' => Hash::make('password'),
                'rol' => 'general',
                'email_verified_at' => $ara,
                'created_at' => $ara,
                'updated_at' => $ara,
            ],
        ]);
    }

    private function crearModelsAvio()
    {
        $models = [
            ['nomModel' => 'Airbus A320',    'files' => 30, 'columnes' => 6, 'seientsTotals' => 180, 'descripcio' => 'Avió de passadís únic, molt comú en rutes europees'],
            ['nomModel' => 'Boeing 737-800', 'files' => 33, 'columnes' => 6, 'seientsTotals' => 189, 'descripcio' => 'Un dels avions més populars del món per rutes curtes i mitjanes'],
            ['nomModel' => 'Airbus A321',    'files' => 36, 'columnes' => 6, 'seientsTotals' => 220, 'descripcio' => 'Versió allargada de l\'A320, més capacitat'],
            ['nomModel' => 'Embraer E190',   'files' => 25, 'columnes' => 4, 'seientsTotals' => 100, 'descripcio' => 'Avió regional per rutes curtes'],
        ];

        $ara = Carbon::now();
        for ($i = 0; $i < count($models); $i++) {
            $models[$i]['created_at'] = $ara;
            $models[$i]['updated_at'] = $ara;
        }
        DB::table('modelsAvio')->insert($models);
    }

    private function crearTarifes()
    {
        $ara = Carbon::now();
        DB::table('tarifes')->insert([
            ['nom' => 'general', 'preu' => 49.99, 'descripcio' => 'Tarifa estàndard per a adults',           'activa' => true, 'created_at' => $ara, 'updated_at' => $ara],
            ['nom' => 'nen',     'preu' => 24.99, 'descripcio' => 'Tarifa infantil (50% descompte)',          'activa' => true, 'created_at' => $ara, 'updated_at' => $ara],
            ['nom' => 'soci',    'preu' => 39.99, 'descripcio' => 'Tarifa especial per a socis (20% descompte)', 'activa' => true, 'created_at' => $ara, 'updated_at' => $ara],
        ]);
    }

    // Vols per avui + demà. Finestra: cutoff 3h, màxim 24h
    private function crearVolsExemple()
    {
        $avui = Carbon::today();
        $dema = Carbon::tomorrow();
        $ara = Carbon::now();

        // Vols d'avui (07:00 - 19:30)
        $volsAvui = [
            ['ext' => 'EXT-VY1001', 'desti' => 'MAD', 'num' => 'VY1001', 'aero' => 'Vueling',           'hora' => '07:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-FR9201', 'desti' => 'MXP', 'num' => 'FR9201', 'aero' => 'Ryanair',            'hora' => '07:30', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-BA479',  'desti' => 'LHR', 'num' => 'BA479',  'aero' => 'British Airways',    'hora' => '08:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-AF1149', 'desti' => 'CDG', 'num' => 'AF1149', 'aero' => 'Air France',         'hora' => '08:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-KL1674', 'desti' => 'AMS', 'num' => 'KL1674', 'aero' => 'KLM',                'hora' => '09:00', 'model' => 4, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-LH1137', 'desti' => 'FRA', 'num' => 'LH1137', 'aero' => 'Lufthansa',          'hora' => '09:30', 'model' => 3, 'estat' => 'retardat',  'estatExt' => 'delayed'],
            ['ext' => 'EXT-IB3216', 'desti' => 'MAD', 'num' => 'IB3216', 'aero' => 'Iberia',             'hora' => '10:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-U23812', 'desti' => 'MXP', 'num' => 'U23812', 'aero' => 'easyJet',            'hora' => '10:30', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-TP1059', 'desti' => 'LIS', 'num' => 'TP1059', 'aero' => 'TAP Air Portugal',   'hora' => '11:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-FR6341', 'desti' => 'FCO', 'num' => 'FR6341', 'aero' => 'Ryanair',            'hora' => '11:30', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-VY6102', 'desti' => 'ATH', 'num' => 'VY6102', 'aero' => 'Vueling',            'hora' => '12:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-W64501', 'desti' => 'BUD', 'num' => 'W64501', 'aero' => 'Wizz Air',           'hora' => '12:30', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-SK1598', 'desti' => 'CPH', 'num' => 'SK1598', 'aero' => 'SAS',                'hora' => '13:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-AZ0073', 'desti' => 'FCO', 'num' => 'AZ0073', 'aero' => 'ITA Airways',        'hora' => '13:30', 'model' => 1, 'estat' => 'retardat',  'estatExt' => 'delayed'],
            ['ext' => 'EXT-TK1854', 'desti' => 'IST', 'num' => 'TK1854', 'aero' => 'Turkish Airlines',   'hora' => '14:00', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-EW7542', 'desti' => 'DUS', 'num' => 'EW7542', 'aero' => 'Eurowings',          'hora' => '14:30', 'model' => 4, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-VY2050', 'desti' => 'PMI', 'num' => 'VY2050', 'aero' => 'Vueling',            'hora' => '15:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-FR8877', 'desti' => 'STN', 'num' => 'FR8877', 'aero' => 'Ryanair',            'hora' => '16:30', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-IB3450', 'desti' => 'MAD', 'num' => 'IB3450', 'aero' => 'Iberia',             'hora' => '17:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-LX1953', 'desti' => 'ZRH', 'num' => 'LX1953', 'aero' => 'Swiss',              'hora' => '18:30', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-VY1305', 'desti' => 'ORY', 'num' => 'VY1305', 'aero' => 'Vueling',            'hora' => '19:30', 'model' => 1, 'estat' => 'retardat',  'estatExt' => 'delayed'],
        ];

        // Vols de demà matí (07:00 - 14:00)
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

        // Crear controlCompraVol per cada vol
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
    }

    // Helper: processa vols d'un dia i els afegeix als arrays
    private function processarVols($vols, $dia, $ara, &$volsExterns, &$volsInterns)
    {
        for ($i = 0; $i < count($vols); $i++) {
            $v = $vols[$i];
            $parts = explode(':', $v['hora']);
            $sortidaCarbon = $dia->copy()->setHour(intval($parts[0]))->setMinute(intval($parts[1]))->setSecond(0);
            $sortida = $sortidaCarbon->format('Y-m-d H:i:s');
            $sortidaReal = null;

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
                'created_at' => $ara,
                'updated_at' => $ara,
            ];
        }
    }
}
