<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Vol intern venible del sistema
class VolIntern extends Model
{
    protected $table = 'volsInterns';

    protected $fillable = [
        'externalId',
        'origenIata',
        'destiIata',
        'dataHoraSortida',
        'estat',
        'modelAvioId',
        'capacitatCompra',
        'maximBitlletsPerCompra',
        'vol_entrant_origen',
        'hora_arribada_esperada',
        'estat_venda',
    ];

    protected function casts(): array
    {
        return [
            'dataHoraSortida' => 'datetime',
            'hora_arribada_esperada' => 'datetime',
        ];
    }

    // Relació: model d'avió assignat
    public function modelAvio()
    {
        return $this->belongsTo(ModelAvio::class, 'modelAvioId');
    }

    // Relació: control de compra (cua)
    public function controlCompra()
    {
        return $this->hasOne(ControlCompraVol::class, 'volId');
    }

    // Relació: entrades a la cua
    public function cuaEntrades()
    {
        return $this->hasMany(CuaCompraVol::class, 'volId');
    }

    // Relació: holds actius de seients
    public function holdsSeients()
    {
        return $this->hasMany(HoldSeient::class, 'volId');
    }

    // Relació: compres fetes
    public function compres()
    {
        return $this->hasMany(Compra::class, 'volId');
    }

    // Relació: bitllets venuts
    public function bitllets()
    {
        return $this->hasMany(Bitllet::class, 'volId');
    }

    // Relació: dades del vol extern (cache)
    public function volExtern()
    {
        return $this->belongsTo(VolExternCache::class, 'externalId', 'externalId');
    }
}
