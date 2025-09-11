<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\AfiliadoApiController;
use App\Http\Controllers\Api\SeccionApiController;
use App\Http\Controllers\Api\ActividadApiController;
use App\Http\Controllers\Api\MapaApiController;
use App\Http\Controllers\Api\ReporteApiController;
use App\Http\Controllers\Api\DeviceApiController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('api.auth.login');

    Route::post('/devices', [DeviceApiController::class, 'store'])->name('api.devices.store');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me'])->name('api.auth.me');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');

        Route::apiResource('afiliados', AfiliadoApiController::class)->names('api.afiliados')->middleware([
            'permission:afiliados.ver|afiliados.crear|afiliados.editar|afiliados.borrar'
        ]);
        Route::post('/registro', [AfiliadoApiController::class, 'store'])->name('api.registro.store')->middleware('permission:afiliados.crear');

        Route::apiResource('secciones', SeccionApiController::class)->names('api.secciones')->middleware([
            'permission:secciones.ver|secciones.crear|secciones.editar|secciones.borrar'
        ]);

        Route::get('/actividades/feed', [ActividadApiController::class, 'feed'])->name('api.actividades.feed')->middleware('permission:actividades.ver');
        Route::apiResource('actividades', ActividadApiController::class)->names('api.actividades')->middleware([
            'permission:actividades.ver|actividades.crear|actividades.editar|actividades.borrar'
        ]);

        Route::get('/mapa', [MapaApiController::class, 'index'])->name('api.mapa.index')->middleware('permission:mapa.ver');
        Route::get('/mapa/data', [MapaApiController::class, 'data'])->name('api.mapa.data')->middleware('permission:mapa.ver');

        Route::get('/reportes/secciones', [ReporteApiController::class, 'secciones'])->name('api.reportes.secciones')->middleware('permission:reportes.ver');
        Route::get('/reportes/capturistas', [ReporteApiController::class, 'capturistas'])->name('api.reportes.capturistas')->middleware('permission:reportes.ver');

        Route::get('/comunicados', [\App\Http\Controllers\Api\ComunicadoApiController::class, 'index'])->name('api.comunicados.index')->middleware('permission:comunicados.ver');

        Route::get('/comunicados/{id}', [\App\Http\Controllers\Api\ComunicadoApiController::class, 'show'])->name('api.comunicados.show')->middleware('permission:comunicados.ver');

    });
});

Route::fallback(function () {
    return response()->json(['message' => 'Ruta no encontrada'], 404);
});
