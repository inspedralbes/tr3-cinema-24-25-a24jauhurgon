<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VolsController;
use App\Http\Controllers\Api\CuaController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| Rutes API - Última Hora BCN
|--------------------------------------------------------------------------
*/

// --- Autenticació ---
Route::post('/auth/registre', [AuthController::class, 'registre']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Google Login (Fase 5)
Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/perfil', [AuthController::class, 'perfil']);
});

// --- Vols (públic) ---
Route::get('/vols', [VolsController::class, 'llistat']);
Route::get('/vols/{id}', [VolsController::class, 'detall']);
Route::get('/tarifes', [VolsController::class, 'tarifes']);

// --- Cua (públic amb clientId) ---
Route::post('/cua/{volId}/entrar', [CuaController::class, 'entrar']);
Route::get('/cua/{volId}/posicio', [CuaController::class, 'posicio']);
Route::post('/cua/{volId}/sortir', [CuaController::class, 'sortir']);

// --- Compra / Seients ---
Route::get('/compra/{volId}/seatmap', [CompraController::class, 'seatmap']);
Route::post('/compra/{volId}/bloquejar', [CompraController::class, 'bloquejarSeient']);
Route::post('/compra/{volId}/alliberar', [CompraController::class, 'alliberarSeient']);
Route::post('/compra/{volId}/confirmar', [CompraController::class, 'confirmar']);

Route::get('/compra/{compraId}/pdf', [CompraController::class, 'descarregarPdf']);

// --- Historial de vols (públic) ---
Route::get('/vols/historial', [VolsController::class, 'historial']);

// --- Admin (protegit per auth + middleware esAdmin) ---
Route::middleware(['auth:sanctum', \App\Http\Middleware\EsAdmin::class])->prefix('admin')->group(function () {
    // Models d'avió
    Route::get('/models-avio', [AdminController::class, 'llistatModelsAvio']);
    Route::post('/models-avio', [AdminController::class, 'crearModelAvio']);
    Route::put('/models-avio/{id}', [AdminController::class, 'actualitzarModelAvio']);
    Route::delete('/models-avio/{id}', [AdminController::class, 'eliminarModelAvio']);

    // Vols interns
    Route::get('/vols-interns', [AdminController::class, 'llistatVolsInterns']);
    Route::post('/vols-interns', [AdminController::class, 'crearVolIntern']);
    Route::put('/vols-interns/{id}', [AdminController::class, 'actualitzarVolIntern']);
    Route::delete('/vols-interns/{id}', [AdminController::class, 'eliminarVolIntern']);
    Route::post('/vols-interns/{id}/force-status', [AdminController::class, 'forceStatus']);

    // Check-in (QR Scanner)
    Route::post('/checkin', [AdminController::class, 'checkinQR']);

    // Monitorització i Mapes
    Route::get('/monitoritzacio', [AdminController::class, 'monitoritzacio']);
    Route::get('/vols-interns/{id}/seients', [AdminController::class, 'seients']);

    // Gestió d'Usuaris
    Route::get('/usuaris', [AdminController::class, 'llistatUsuaris']);
    Route::post('/usuaris/{id}/toggle-soci', [AdminController::class, 'toggleRolUsuari']);
});
