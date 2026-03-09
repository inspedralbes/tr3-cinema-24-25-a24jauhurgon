<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model d'avió: defineix files, columnes i seients
class ModelAvio extends Model
{
    protected $table = 'modelsAvio';

    protected $fillable = [
        'nomModel',
        'files',
        'columnes',
        'seientsTotals',
        'descripcio',
    ];

    // Relació: vols que utilitzen aquest model
    public function volsInterns()
    {
        return $this->hasMany(VolIntern::class, 'modelAvioId');
    }
}
