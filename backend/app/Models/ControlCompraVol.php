<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Control de capacitat de compra simultània per vol
class ControlCompraVol extends Model
{
    protected $table = 'controlCompraVol';

    protected $fillable = [
        'volId',
        'actius',
        'capacitat',
    ];

    // Relació: vol intern associat
    public function volIntern()
    {
        return $this->belongsTo(VolIntern::class, 'volId');
    }
}
