<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Tarifa de bitllet (general, nen, soci)
class Tarifa extends Model
{
    protected $table = 'tarifes';

    protected $fillable = [
        'nom',
        'preu',
        'descripcio',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'preu' => 'decimal:2',
        ];
    }
}
