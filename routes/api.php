<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\AfiliadoApiController;
use App\Http\Controllers\Api\SeccionApiController;
use App\Http\Controllers\Api\ActividadApiController;
use App\Http\Controllers\Api\MapaApiController;
use App\Http\Controllers\Api\ReporteApiController;
use App\Http\Controllers\Api\DeviceApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\FeedApiController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('api.auth.login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me'])->name('api.auth.me');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');

        Route::post('/devices', [DeviceApiController::class, 'store'])->name('api.devices.store');
        Route::get('/feed', [FeedApiController::class, 'index'])
            ->name('api.feed.index')->middleware('permission:lonas.ver');

        Route::get('/afiliados', [AfiliadoApiController::class, 'index'])->middleware('permission:afiliados.ver');
        Route::post('/afiliados', [AfiliadoApiController::class, 'store'])->middleware('permission:afiliados.crear');
        Route::get('/afiliados/{afiliado}', [AfiliadoApiController::class, 'show'])->middleware('permission:afiliados.ver');
        Route::match(['put', 'patch'], '/afiliados/{afiliado}', [AfiliadoApiController::class, 'update'])->middleware('permission:afiliados.editar');
        Route::delete('/afiliados/{afiliado}', [AfiliadoApiController::class, 'destroy'])->middleware('permission:afiliados.borrar');
        Route::post('/registro', [AfiliadoApiController::class, 'store'])->name('api.registro.store')->middleware('permission:afiliados.crear');

        Route::get('/secciones', [SeccionApiController::class, 'index'])->middleware('permission:secciones.ver');
        Route::post('/secciones', [SeccionApiController::class, 'store'])->middleware('permission:secciones.crear');
        Route::get('/secciones/{seccion}', [SeccionApiController::class, 'show'])->middleware('permission:secciones.ver');
        Route::match(['put', 'patch'], '/secciones/{seccion}', [SeccionApiController::class, 'update'])->middleware('permission:secciones.editar');
        Route::delete('/secciones/{seccion}', [SeccionApiController::class, 'destroy'])->middleware('permission:secciones.borrar');

        Route::get('/actividades/feed', [ActividadApiController::class, 'feed'])->name('api.actividades.feed')->middleware('permission:actividades.ver');
        Route::get('/actividades', [ActividadApiController::class, 'index'])->middleware('permission:actividades.ver');
        Route::post('/actividades', [ActividadApiController::class, 'store'])->middleware('permission:actividades.crear');
        Route::get('/actividades/{actividad}', [ActividadApiController::class, 'show'])->middleware('permission:actividades.ver');
        Route::match(['put', 'patch'], '/actividades/{actividad}', [ActividadApiController::class, 'update'])->middleware('permission:actividades.editar');
        Route::delete('/actividades/{actividad}', [ActividadApiController::class, 'destroy'])->middleware('permission:actividades.borrar');

        Route::get('/mapa', [MapaApiController::class, 'index'])->name('api.mapa.index')->middleware('permission:mapa.ver');
        Route::get('/mapa/data', [MapaApiController::class, 'data'])->name('api.mapa.data')->middleware('permission:mapa.ver');

        Route::get('/reportes/afiliados', [ReporteApiController::class, 'afiliados'])->name('api.reportes.afiliados')->middleware('permission:reportes.ver');
        Route::get('/reportes/secciones', [ReporteApiController::class, 'secciones'])->name('api.reportes.secciones')->middleware('permission:reportes.ver');
        Route::get('/reportes/capturistas', [ReporteApiController::class, 'capturistas'])->name('api.reportes.capturistas')->middleware('permission:reportes.ver');

        Route::get('/lonas/mapa', [\App\Http\Controllers\Api\LonaApiController::class, 'mapData'])
            ->name('api.lonas.map')->middleware('permission:lonas.ver');
        Route::get('/lonas/{lona}/foto', [\App\Http\Controllers\Api\LonaApiController::class, 'photo'])
            ->name('api.lonas.photo')->middleware('permission:lonas.ver');
        Route::get('/lonas', [\App\Http\Controllers\Api\LonaApiController::class, 'index'])
            ->name('api.lonas.index')->middleware('permission:lonas.ver');
        Route::post('/lonas', [\App\Http\Controllers\Api\LonaApiController::class, 'store'])
            ->name('api.lonas.store')->middleware('permission:lonas.crear');
        Route::get('/lonas/{lona}', [\App\Http\Controllers\Api\LonaApiController::class, 'show'])
            ->name('api.lonas.show')->middleware('permission:lonas.ver');
        Route::match(['put', 'patch'], '/lonas/{lona}', [\App\Http\Controllers\Api\LonaApiController::class, 'update'])
            ->name('api.lonas.update')->middleware('permission:lonas.editar');
        Route::delete('/lonas/{lona}', [\App\Http\Controllers\Api\LonaApiController::class, 'destroy'])
            ->name('api.lonas.destroy')->middleware('permission:lonas.borrar');

        Route::get('/comunicados', [\App\Http\Controllers\Api\ComunicadoApiController::class, 'index'])->name('api.comunicados.index')->middleware('permission:comunicados.ver');

        Route::get('/comunicados/{id}', [\App\Http\Controllers\Api\ComunicadoApiController::class, 'show'])->name('api.comunicados.show')->middleware('permission:comunicados.ver');

        Route::prefix('admin')->group(function () {
            Route::get('/usuarios', [AdminApiController::class, 'users'])->middleware('permission:usuarios.ver');
            Route::post('/usuarios', [AdminApiController::class, 'storeUser'])->middleware('permission:usuarios.crear');
            Route::put('/usuarios/{user}', [AdminApiController::class, 'updateUser'])->middleware('permission:usuarios.editar');
            Route::delete('/usuarios/{user}', [AdminApiController::class, 'destroyUser'])->middleware('permission:usuarios.borrar');

            Route::get('/roles', [AdminApiController::class, 'roles'])->middleware('permission:roles.ver');
            Route::post('/roles', [AdminApiController::class, 'storeRole'])->middleware('permission:roles.crear');
            Route::put('/roles/{role}', [AdminApiController::class, 'updateRole'])->middleware('permission:roles.editar');
            Route::delete('/roles/{role}', [AdminApiController::class, 'destroyRole'])->middleware('permission:roles.borrar');
            Route::get('/roles/{role}/permisos', [AdminApiController::class, 'rolePermissions'])->middleware('permission:permisos.ver');
            Route::put('/roles/{role}/permisos', [AdminApiController::class, 'updateRolePermissions'])->middleware('permission:permisos.editar');

            Route::get('/comunicados', [AdminApiController::class, 'comunicados'])->middleware('permission:comunicados.ver');
            Route::post('/comunicados', [AdminApiController::class, 'storeComunicado'])->middleware('permission:comunicados.crear');
            Route::put('/comunicados/{comunicado}', [AdminApiController::class, 'updateComunicado'])->middleware('permission:comunicados.editar');
            Route::delete('/comunicados/{comunicado}', [AdminApiController::class, 'destroyComunicado'])->middleware('permission:comunicados.borrar');

            Route::get('/app', [AdminApiController::class, 'appSettings'])->middleware('permission:settings.ver');
            Route::put('/app', [AdminApiController::class, 'updateAppSettings'])->middleware('permission:settings.editar');
        });

    });
});

Route::fallback(function () {
    return response()->json(['message' => 'Ruta no encontrada'], 404);
});
