<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Bitllet individual dins d'una compra
class Bitllet extends Model
{
    protected $table = 'bitllets';

    protected $fillable = [
        'compraId',
        'volId',
        'fila',
        'columna',
        'tipus',
        'preu',
        'nomPassatger',
        'hora_embarcament',
    ];

    // Relació: compra pare
    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compraId');
    }

    // Relació: vol intern
    public function volIntern()
    {
        return $this->belongsTo(VolIntern::class, 'volId');
    }
}
