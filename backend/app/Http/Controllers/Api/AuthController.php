<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

// Controlador d'autenticació (login, registre, logout, perfil)
class AuthController extends Controller
{
    /**
     * Registra un nou usuari a l'aplicació amb rol 'general' per defecte.
     * Guarda la contrasenya encriptada, genera un Token d'accés (Sanctum) i notifica 
     * en temps real al Panell d'Administrador que hi ha un nou registre.
     * 
     * @param Request $request Dades del formulari (name, email, password, password_confirmation).
     * @return \Illuminate\Http\JsonResponse Dades de l'usuari creat i el seu Token d'Autenticació.
     */
    public function registre(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $usuari = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => 'general',
        ]);

        $token = $usuari->createToken('auth-token')->plainTextToken;

        // Notificar al panell d'Admin en temps real
        try {
            Http::timeout(0.2)->post('http://socket:3002/emit', [
                'event' => 'nou_usuari_registrat',
                'payload' => $usuari
            ]);
        } catch (\Exception $e) { /* Silenciós si falla el socket */ }

        return response()->json([
            'usuari' => $usuari,
            'token' => $token,
        ], 201);
    }

    /**
     * Inicia la sessió d'un usuari existent verificant les credencials.
     * Si l'email i la contrasenya coincideixen, genera i retorna un nou Token Sanctum.
     * 
     * @param Request $request Dades del login (email, password).
     * @return \Illuminate\Http\JsonResponse Dades de l'usuari i el seu Token d'Autenticació.
     * @throws ValidationException Si les credencials són invàlides.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $usuari = User::where('email', $request->email)->first();

        if (!$usuari || !Hash::check($request->password, $usuari->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les credencials no són correctes.'],
            ]);
        }

        $token = $usuari->createToken('auth-token')->plainTextToken;

        return response()->json([
            'usuari' => $usuari,
            'token' => $token,
        ]);
    }

    /**
     * Tanca la sessió de l'usuari actual.
     * Revoca i elimina de la base de dades el Token Sanctum que estava fent servir.
     * 
     * @param Request $request La petició autenticada actual.
     * @return \Illuminate\Http\JsonResponse Missatge d'èxit ("Sessió tancada").
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'missatge' => 'Sessió tancada correctament.',
        ]);
    }

    /**
     * Retorna les dades completes del perfil de l'usuari que fa la petició.
     * Útil per recarregar l'estat global del Frontend (Pinia) a l'entrar a la web.
     * 
     * @param Request $request La petició autenticada.
     * @return \Illuminate\Http\JsonResponse Objecte JSON amb el model complet de l'usuari.
     */
    public function perfil(Request $request)
    {
        return response()->json([
            'usuari' => $request->user(),
        ]);
    }

    /**
     * Inicia el flux d'autenticació delegada OAuth2 amb Google.
     * Redirigeix a l'usuari cap a la pantalla d'inici de sessió oficial de Google.
     * 
     * @return \Illuminate\Http\RedirectResponse Redirecció HTTP cap a Google.
     */
    public function googleRedirect()
    {
        return \Laravel\Socialite\Facades\Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * URL de Retorn (Callback) un cop l'usuari s'ha identificat correctament a Google.
     * Si l'usuari és nou, el registra automàticament; si l'email ja existia, vincula els comptes.
     * Finalment, redirigeix de tornada al Frontend (`/vols`) injectant el Token d'Accés via URL per auto-loguejar-lo.
     * 
     * @return \Illuminate\Http\RedirectResponse Redirecció al Frontend amb Token incrustat o missatge d'error.
     */
    public function googleCallback()
    {
        try {
            $googleUser = \Laravel\Socialite\Facades\Socialite::driver('google')->stateless()->user();
            
            // Buscar o crear usuari
            $usuari = User::where('email', $googleUser->getEmail())->first();
            $esNou = false;

            if (!$usuari) {
                $usuari = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(uniqid()), // Random password per usuaris de Google
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'rol' => 'general',
                ]);
                $esNou = true;
            } elseif (!$usuari->google_id) {
                // Si l'usuari ja existia amb email/pass, vincular Google a posteriori
                $usuari->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $usuari->avatar ?? $googleUser->getAvatar(),
                ]);
            }

            $token = $usuari->createToken('auth-token')->plainTextToken;

            if ($esNou) {
                try {
                    Http::timeout(0.2)->post('http://socket:3002/emit', [
                        'event' => 'nou_usuari_registrat',
                        'payload' => $usuari
                    ]);
                } catch (\Exception $e) { /* Silenciós si falla el socket */ }
            }

            // Redirigim al frontend amb el token a la URL
            $frontendUrl = config('app.frontend_url');
            return redirect($frontendUrl . '/vols?token=' . $token . '&usuari=' . urlencode(json_encode($usuari)));
            
        } catch (\Exception $e) {
            $frontendUrl = config('app.frontend_url');
            return redirect($frontendUrl . '/?error=' . urlencode('Error amb Google: ' . $e->getMessage()));
        }
    }
}
