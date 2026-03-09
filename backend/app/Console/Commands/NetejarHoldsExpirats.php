<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HoldSeient;
use Carbon\Carbon;

// Comanda per netejar holds de seients expirats
// Executar amb: php artisan holds:netejar
// Programar al scheduler per executar cada minut
class NetejarHoldsExpirats extends Command
{
    protected $signature = 'holds:netejar';
    protected $description = 'Elimina els holds de seients que han expirat';

    public function handle()
    {
        $ara = Carbon::now();
        $eliminats = HoldSeient::where('expiraAt', '<', $ara)->delete();

        $this->info('Holds expirats eliminats: ' . $eliminats);
        return Command::SUCCESS;
    }
}
