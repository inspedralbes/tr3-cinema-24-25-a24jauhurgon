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
            ['ext' => 'EXT-IB3216', 'desti' => 'AGP', 'num' => 'IB3216', 'aero' => 'Iberia',            'hora' => '07:30', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-UX4021', 'desti' => 'TFN', 'num' => 'UX4021', 'aero' => 'Air Europa',        'hora' => '08:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-VY2241', 'desti' => 'SVQ', 'num' => 'VY2241', 'aero' => 'Vueling',           'hora' => '08:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-FR6341', 'desti' => 'LPA', 'num' => 'FR6341', 'aero' => 'Ryanair',            'hora' => '09:00', 'model' => 4, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-IB3450', 'desti' => 'VLC', 'num' => 'IB3450', 'aero' => 'Iberia',             'hora' => '09:30', 'model' => 3, 'estat' => 'retardat',  'estatExt' => 'delayed'],
            ['ext' => 'EXT-VY1305', 'desti' => 'BIO', 'num' => 'VY1305', 'aero' => 'Vueling',            'hora' => '10:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-UX4112', 'desti' => 'ACE', 'num' => 'UX4112', 'aero' => 'Air Europa',        'hora' => '10:30', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-TP1059', 'desti' => 'LIS', 'num' => 'TP1059', 'aero' => 'TAP Air Portugal',   'hora' => '11:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-VY1512', 'desti' => 'SCQ', 'num' => 'VY1512', 'aero' => 'Vueling',            'hora' => '11:30', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-FR8877', 'desti' => 'FUE', 'num' => 'FR8877', 'aero' => 'Ryanair',            'hora' => '12:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-IB3012', 'desti' => 'MAD', 'num' => 'IB3012', 'aero' => 'Iberia',             'hora' => '12:30', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-VY1042', 'desti' => 'OPO', 'num' => 'VY1042', 'aero' => 'Vueling',            'hora' => '13:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-UX4050', 'desti' => 'TFS', 'num' => 'UX4050', 'aero' => 'Air Europa',        'hora' => '13:30', 'model' => 1, 'estat' => 'retardat',  'estatExt' => 'delayed'],
            ['ext' => 'EXT-VY2110', 'desti' => 'AGP', 'num' => 'VY2110', 'aero' => 'Vueling',            'hora' => '14:00', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-FR5512', 'desti' => 'SVQ', 'num' => 'FR5512', 'aero' => 'Ryanair',            'hora' => '14:30', 'model' => 4, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-VY2050', 'desti' => 'ALC', 'num' => 'VY2050', 'aero' => 'Vueling',            'hora' => '15:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-IB3120', 'desti' => 'MAD', 'num' => 'IB3120', 'aero' => 'Iberia',             'hora' => '16:30', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-UX4118', 'desti' => 'LPA', 'num' => 'UX4118', 'aero' => 'Air Europa',        'hora' => '17:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-VY2245', 'desti' => 'VLC', 'num' => 'VY2245', 'aero' => 'Vueling',            'hora' => '18:30', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-FR9205', 'desti' => 'SCQ', 'num' => 'FR9205', 'aero' => 'Ryanair',            'hora' => '19:30', 'model' => 1, 'estat' => 'retardat',  'estatExt' => 'delayed'],
        ];

        // Vols de demà matí (07:00 - 14:00)
        $volsDema = [
            ['ext' => 'EXT-T-VY1002', 'desti' => 'MAD', 'num' => 'VY1002', 'aero' => 'Vueling',           'hora' => '07:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-IB3220', 'desti' => 'SVQ', 'num' => 'IB3220', 'aero' => 'Iberia',             'hora' => '08:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-UX4023', 'desti' => 'TFN', 'num' => 'UX4023', 'aero' => 'Air Europa',        'hora' => '09:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-VY1307', 'desti' => 'BIO', 'num' => 'VY1307', 'aero' => 'Vueling',            'hora' => '09:30', 'model' => 4, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-FR6343', 'desti' => 'LPA', 'num' => 'FR6343', 'aero' => 'Ryanair',            'hora' => '10:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-IB3218', 'desti' => 'ACE', 'num' => 'IB3218', 'aero' => 'Iberia',             'hora' => '10:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-TP1061', 'desti' => 'LIS', 'num' => 'TP1061', 'aero' => 'TAP Air Portugal',   'hora' => '11:00', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-VY1514', 'desti' => 'SCQ', 'num' => 'VY1514', 'aero' => 'Vueling',            'hora' => '11:30', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-FR8879', 'desti' => 'FUE', 'num' => 'FR8879', 'aero' => 'Ryanair',            'hora' => '12:00', 'model' => 3, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-UX4120', 'desti' => 'LPA', 'num' => 'UX4120', 'aero' => 'Air Europa',        'hora' => '13:00', 'model' => 2, 'estat' => 'programat', 'estatExt' => 'scheduled'],
            ['ext' => 'EXT-T-VY1044', 'desti' => 'OPO', 'num' => 'VY1044', 'aero' => 'Vueling',            'hora' => '14:00', 'model' => 1, 'estat' => 'programat', 'estatExt' => 'scheduled'],
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
