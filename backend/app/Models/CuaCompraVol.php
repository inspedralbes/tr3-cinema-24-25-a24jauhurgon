<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Entrada a la cua d'espera per comprar un vol
class CuaCompraVol extends Model
{
    protected $table = 'cuaCompraVol';

    protected $fillable = [
        'volId',
        'clientId',
        'estat',
        'ticket',
        'ticketExpiraAt',
    ];

    protected function casts(): array
    {
        return [
            'ticketExpiraAt' => 'datetime',
        ];
    }

    // Relació: vol intern associat
    public function volIntern()
    {
        return $this->belongsTo(VolIntern::class, 'volId');
    }
}
