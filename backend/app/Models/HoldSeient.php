<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Hold temporal d'un seient (bloqueig de 3 minuts)
class HoldSeient extends Model
{
    protected $table = 'holdsSeients';

    protected $fillable = [
        'volId',
        'clientId',
        'fila',
        'columna',
        'expiraAt',
    ];

    protected function casts(): array
    {
        return [
            'expiraAt' => 'datetime',
        ];
    }

    // Relació: vol intern associat
    public function volIntern()
    {
        return $this->belongsTo(VolIntern::class, 'volId');
    }
}
