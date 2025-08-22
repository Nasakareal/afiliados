<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AfiliadoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\ActividadController;
use App\Http\Controllers\MapaController;
use App\Http\Controllers\ReporteController;

use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\PermissionController;
use App\Http\Controllers\Settings\AppSettingController;

/*
|--------------------------------------------------------------------------
| Público
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('welcome');

// Carga /login, /register, /logout (Breeze)
if (file_exists(base_path('routes/auth.php'))) {
    require __DIR__.'/auth.php';
}

/*
|--------------------------------------------------------------------------
| Protegido (auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Afiliados (convencidos) – CRUD + “registro” (atajo al create)
    Route::get('/afiliados',                 [AfiliadoController::class, 'index'])->name('afiliados.index')->middleware('permission:afiliados.ver');
    Route::get('/afiliados/create',          [AfiliadoController::class, 'create'])->name('afiliados.create')->middleware('permission:afiliados.crear');
    Route::post('/afiliados',                [AfiliadoController::class, 'store'])->name('afiliados.store')->middleware('permission:afiliados.crear');
    Route::get('/afiliados/{afiliado}',      [AfiliadoController::class, 'show'])->name('afiliados.show')->middleware('permission:afiliados.ver');
    Route::get('/afiliados/{afiliado}/edit', [AfiliadoController::class, 'edit'])->name('afiliados.edit')->middleware('permission:afiliados.editar');
    Route::put('/afiliados/{afiliado}',      [AfiliadoController::class, 'update'])->name('afiliados.update')->middleware('permission:afiliados.editar');
    Route::delete('/afiliados/{afiliado}',   [AfiliadoController::class, 'destroy'])->name('afiliados.destroy')->middleware('permission:afiliados.borrar');

    Route::get('/registro', [AfiliadoController::class, 'create'])->name('registro')->middleware('permission:afiliados.crear');

    // Secciones – CRUD
    Route::get('/secciones',                 [SeccionController::class, 'index'])->name('secciones.index')->middleware('permission:secciones.ver');
    Route::get('/secciones/create',          [SeccionController::class, 'create'])->name('secciones.create')->middleware('permission:secciones.crear');
    Route::post('/secciones',                [SeccionController::class, 'store'])->name('secciones.store')->middleware('permission:secciones.crear');
    Route::get('/secciones/{seccion}',       [SeccionController::class, 'show'])->name('secciones.show')->middleware('permission:secciones.ver');
    Route::get('/secciones/{seccion}/edit',  [SeccionController::class, 'edit'])->name('secciones.edit')->middleware('permission:secciones.editar');
    Route::put('/secciones/{seccion}',       [SeccionController::class, 'update'])->name('secciones.update')->middleware('permission:secciones.editar');
    Route::delete('/secciones/{seccion}',    [SeccionController::class, 'destroy'])->name('secciones.destroy')->middleware('permission:secciones.borrar');

    // Actividades (calendario) – CRUD + feed
    Route::get('/calendario',                  [ActividadController::class, 'index'])->name('calendario.index')->middleware('permission:actividades.ver');
    Route::get('/actividades/feed',            [ActividadController::class, 'feed'])->name('actividades.feed')->middleware('permission:actividades.ver');

    Route::get('/actividades',                 [ActividadController::class, 'list'])->name('actividades.index')->middleware('permission:actividades.ver');
    Route::get('/actividades/create',          [ActividadController::class, 'create'])->name('actividades.create')->middleware('permission:actividades.crear');
    Route::post('/actividades',                [ActividadController::class, 'store'])->name('actividades.store')->middleware('permission:actividades.crear');
    Route::get('/actividades/{actividad}',     [ActividadController::class, 'show'])->name('actividades.show')->middleware('permission:actividades.ver');
    Route::get('/actividades/{actividad}/edit',[ActividadController::class, 'edit'])->name('actividades.edit')->middleware('permission:actividades.editar');
    Route::put('/actividades/{actividad}',     [ActividadController::class, 'update'])->name('actividades.update')->middleware('permission:actividades.editar');
    Route::delete('/actividades/{actividad}',  [ActividadController::class, 'destroy'])->name('actividades.destroy')->middleware('permission:actividades.borrar');

    // Mapa
    Route::get('/mapa', [MapaController::class, 'index'])->name('mapa.index')->middleware('permission:mapa.ver');

    // Reportes
    Route::get('/reportes/secciones',   [ReporteController::class, 'secciones'])->name('reportes.secciones')->middleware('permission:reportes.ver');
    Route::get('/reportes/capturistas', [ReporteController::class, 'capturistas'])->name('reportes.capturistas')->middleware('permission:reportes.ver');

    // Settings (usuarios, roles, permisos, app)
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index')->middleware('permission:settings.ver');

        // Usuarios
        Route::get('/usuarios',                 [UserController::class, 'index'])->name('usuarios.index')->middleware('permission:usuarios.ver');
        Route::get('/usuarios/create',          [UserController::class, 'create'])->name('usuarios.create')->middleware('permission:usuarios.crear');
        Route::post('/usuarios',                [UserController::class, 'store'])->name('usuarios.store')->middleware('permission:usuarios.crear');
        Route::get('/usuarios/{user}',          [UserController::class, 'show'])->name('usuarios.show')->middleware('permission:usuarios.ver');
        Route::get('/usuarios/{user}/edit',     [UserController::class, 'edit'])->name('usuarios.edit')->middleware('permission:usuarios.editar');
        Route::put('/usuarios/{user}',          [UserController::class, 'update'])->name('usuarios.update')->middleware('permission:usuarios.editar');
        Route::delete('/usuarios/{user}',       [UserController::class, 'destroy'])->name('usuarios.destroy')->middleware('permission:usuarios.borrar');

        // Roles
        Route::get('/roles',                    [RoleController::class, 'index'])->name('roles.index')->middleware('permission:roles.ver');
        Route::get('/roles/create',             [RoleController::class, 'create'])->name('roles.create')->middleware('permission:roles.crear');
        Route::post('/roles',                   [RoleController::class, 'store'])->name('roles.store')->middleware('permission:roles.crear');
        Route::get('/roles/{role}',             [RoleController::class, 'show'])->name('roles.show')->middleware('permission:roles.ver');
        Route::get('/roles/{role}/edit',        [RoleController::class, 'edit'])->name('roles.edit')->middleware('permission:roles.editar');
        Route::put('/roles/{role}',             [RoleController::class, 'update'])->name('roles.update')->middleware('permission:roles.editar');
        Route::delete('/roles/{role}',          [RoleController::class, 'destroy'])->name('roles.destroy')->middleware('permission:roles.borrar');

        // Permisos
        Route::get('/permisos',                 [PermissionController::class, 'index'])->name('permisos.index')->middleware('permission:permisos.ver');
        Route::get('/permisos/create',          [PermissionController::class, 'create'])->name('permisos.create')->middleware('permission:permisos.crear');
        Route::post('/permisos',                [PermissionController::class, 'store'])->name('permisos.store')->middleware('permission:permisos.crear');
        Route::get('/permisos/{permission}',    [PermissionController::class, 'show'])->name('permisos.show')->middleware('permission:permisos.ver');
        Route::get('/permisos/{permission}/edit',[PermissionController::class, 'edit'])->name('permisos.edit')->middleware('permission:permisos.editar');
        Route::put('/permisos/{permission}',    [PermissionController::class, 'update'])->name('permisos.update')->middleware('permission:permisos.editar');
        Route::delete('/permisos/{permission}', [PermissionController::class, 'destroy'])->name('permisos.destroy')->middleware('permission:permisos.borrar');

        // App Settings (bloqueo de captura, etc.)
        Route::get('/app',  [AppSettingController::class, 'edit'])->name('app.edit')->middleware('permission:settings.editar');
        Route::put('/app',  [AppSettingController::class, 'update'])->name('app.update')->middleware('permission:settings.editar');
    });

});
