<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Compra confirmada (pot tenir múltiples bitllets)
class Compra extends Model
{
    protected $table = 'compres';

    protected $fillable = [
        'volId',
        'usuariId',
        'email',
        'total',
    ];

    // Relació: vol intern
    public function volIntern()
    {
        return $this->belongsTo(VolIntern::class, 'volId');
    }

    // Relació: usuari (pot ser null per convidats)
    public function usuari()
    {
        return $this->belongsTo(User::class, 'usuariId');
    }

    // Relació: bitllets de la compra
    public function bitllets()
    {
        return $this->hasMany(Bitllet::class, 'compraId');
    }
}
