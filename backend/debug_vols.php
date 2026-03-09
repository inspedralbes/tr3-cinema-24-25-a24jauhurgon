<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VolIntern;
use Carbon\Carbon;

header('Content-Type: text/plain');
echo "Current Time (now()): " . now()->toDateTimeString() . "\n";
echo "Current Time (Carbon::now()): " . Carbon::now()->toDateTimeString() . "\n";
echo "Timezone: " . config('app.timezone') . "\n";

$total = VolIntern::count();
echo "Total Flights in DB: " . $total . "\n\n";

if ($total > 0) {
    $vols = VolIntern::orderBy('dataHoraSortida', 'asc')->get();
    foreach ($vols as $v) {
        echo "ID: {$v->id} | Desti: {$v->destiIata} | Sortida: {$v->dataHoraSortida} | Origen: {$v->origenIata}\n";
    }
} else {
    echo "NO FLIGHTS FOUND IN DB.\n";
}
