<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Cache de vols externs (aviationstack)
class VolExternCache extends Model
{
    protected $table = 'volsExternsCache';

    protected $fillable = [
        'externalId',
        'origenIata',
        'destiIata',
        'flightNumber',
        'airline',
        'dataHoraSortidaEstimada',
        'dataHoraSortidaReal',
        'estat',
        'rawJson',
    ];

    protected function casts(): array
    {
        return [
            'dataHoraSortidaEstimada' => 'datetime',
            'dataHoraSortidaReal' => 'datetime',
            'rawJson' => 'array',
        ];
    }
}
