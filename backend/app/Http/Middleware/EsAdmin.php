<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Middleware: comprova que l'usuari autenticat te rol admin
class EsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $usuari = $request->user();

        if (!$usuari || $usuari->rol !== 'admin') {
            return response()->json(['missatge' => 'Accés no autoritzat. Cal ser administrador.'], 403);
        }

        return $next($request);
    }
}
