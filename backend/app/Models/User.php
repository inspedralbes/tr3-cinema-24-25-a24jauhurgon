<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// Model d'usuari amb suport per 3 rols: general, premium, admin
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'google_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Comprova si l'usuari es admin
    public function esAdmin()
    {
        return $this->rol === 'admin';
    }

    // Comprova si l'usuari es premium
    public function esPremium()
    {
        return $this->rol === 'premium';
    }

    // Relació: compres de l'usuari
    public function compres()
    {
        return $this->hasMany(Compra::class, 'usuariId');
    }
}
