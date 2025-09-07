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
use App\Http\Controllers\Settings\RolePermissionController;
use App\Http\Controllers\Settings\AppSettingController;
use App\Http\Controllers\Settings\ComunicadoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\ForcePasswordController;

/*
|--------------------------------------------------------------------------
| Público
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('welcome');

// Breeze/Fortify (login, register, etc.)
if (file_exists(base_path('routes/auth.php'))) {
    require __DIR__.'/auth.php';
}

Route::middleware('auth')->group(function () {
    Route::get('/password/force', [ForcePasswordController::class,'form'])->name('password.force.form');
    Route::post('/password/force', [ForcePasswordController::class,'update'])->name('password.force.update');
});

Route::middleware(['auth','force.password.change'])->group(function () {

    Route::get('/dashboard', [DashboardController::class,'index'])->name('dashboard');

    // Afiliados
    Route::get('/afiliados', [AfiliadoController::class,'index'])->name('afiliados.index')->middleware('permission:afiliados.ver');
    Route::get('/afiliados/create', [AfiliadoController::class,'create'])->name('afiliados.create')->middleware('permission:afiliados.crear');
    Route::post('/afiliados', [AfiliadoController::class,'store'])->name('afiliados.store')->middleware('permission:afiliados.crear');
    Route::get('/afiliados/{afiliado}', [AfiliadoController::class,'show'])->name('afiliados.show')->middleware('permission:afiliados.ver');
    Route::get('/afiliados/{afiliado}/edit', [AfiliadoController::class,'edit'])->name('afiliados.edit')->middleware('permission:afiliados.editar');
    Route::put('/afiliados/{afiliado}', [AfiliadoController::class,'update'])->name('afiliados.update')->middleware('permission:afiliados.editar');
    Route::delete('/afiliados/{afiliado}', [AfiliadoController::class,'destroy'])->name('afiliados.destroy')->middleware('permission:afiliados.borrar');

    Route::get('/registro', [AfiliadoController::class,'create'])->name('registro')->middleware('permission:afiliados.crear');

    // Secciones
    Route::get('/secciones',               [SeccionController::class,'index'])->name('secciones.index')->middleware('permission:secciones.ver');
    Route::get('/secciones/create',        [SeccionController::class,'create'])->name('secciones.create')->middleware('permission:secciones.crear');
    Route::post('/secciones',              [SeccionController::class,'store'])->name('secciones.store')->middleware('permission:secciones.crear');
    Route::post('/secciones/import',       [SeccionController::class,'importExcel'])->name('secciones.import')->middleware('permission:secciones.crear');
    Route::get('/secciones/lookup',        [SeccionController::class,'lookup'])->name('secciones.lookup')->middleware('permission:secciones.ver');
    Route::get   ('/secciones/{seccion}',        [SeccionController::class,'show'])->whereNumber('seccion')->name('secciones.show')->middleware('permission:secciones.ver');
    Route::get   ('/secciones/{seccion}/edit',   [SeccionController::class,'edit'])->whereNumber('seccion')->name('secciones.edit')->middleware('permission:secciones.editar');
    Route::put   ('/secciones/{seccion}',        [SeccionController::class,'update'])->whereNumber('seccion')->name('secciones.update')->middleware('permission:secciones.editar');
    Route::delete('/secciones/{seccion}',        [SeccionController::class,'destroy'])->whereNumber('seccion')->name('secciones.destroy')->middleware('permission:secciones.borrar');

    
    // Actividades / Calendario
    Route::get('/calendario', [ActividadController::class,'index'])->name('calendario.index')->middleware('permission:actividades.ver');
    Route::get('/actividades/feed', [ActividadController::class,'feed'])->name('actividades.feed')->middleware('permission:actividades.ver');
    Route::get('/actividades', [ActividadController::class,'list'])->name('actividades.index')->middleware('permission:actividades.ver');
    Route::get('/actividades/create', [ActividadController::class,'create'])->name('actividades.create')->middleware('permission:actividades.crear');
    Route::post('/actividades', [ActividadController::class,'store'])->name('actividades.store')->middleware('permission:actividades.crear');
    Route::get('/actividades/{actividad}', [ActividadController::class,'show'])->name('actividades.show')->middleware('permission:actividades.ver');
    Route::get('/actividades/{actividad}/edit', [ActividadController::class,'edit'])->name('actividades.edit')->middleware('permission:actividades.editar');
    Route::put('/actividades/{actividad}', [ActividadController::class,'update'])->name('actividades.update')->middleware('permission:actividades.editar');
    Route::delete('/actividades/{actividad}', [ActividadController::class,'destroy'])->name('actividades.destroy')->middleware('permission:actividades.borrar');

    // Reportes (con nombres compatibles)
    Route::prefix('reportes')->name('reportes.')->middleware('permission:reportes.ver')->group(function () {
        Route::get('/', [ReporteController::class,'index'])->name('index');

        // Afiliados (el index se llama EXACTO 'reportes.afiliados')
        Route::prefix('afiliados')->group(function () {
            Route::get('/', [ReporteController::class,'afiliados'])->name('afiliados');                 // /reportes/afiliados
            Route::get('/data', [ReporteController::class,'afiliadosData'])->name('afiliados.data');    // /reportes/afiliados/data
            Route::get('/export.xlsx', [ReporteController::class,'afiliadosExportXlsx'])->name('afiliados.export.xlsx');
            Route::get('/facets', [ReporteController::class,'facets'])->name('afiliados.facets');
        });

        // Secciones (index = 'reportes.secciones')
        Route::prefix('secciones')->group(function () {
            Route::get('/', [ReporteController::class,'secciones'])->name('secciones');                 // /reportes/secciones
            Route::get('/data', [ReporteController::class,'seccionesData'])->name('secciones.data');
            Route::get('/export.xlsx', [ReporteController::class,'seccionesExportXlsx'])->name('secciones.export.xlsx');
        });

        // Capturistas (index = 'reportes.capturistas')
        Route::prefix('capturistas')->group(function () {
            Route::get('/', [ReporteController::class,'capturistas'])->name('capturistas');             // /reportes/capturistas
            Route::get('/data', [ReporteController::class,'capturistasData'])->name('capturistas.data');
            Route::get('/export.xlsx', [ReporteController::class,'capturistasExportXlsx'])->name('capturistas.export.xlsx');
        });
    });

    // Mapa
    Route::get('/mapa', [MapaController::class,'index'])->name('mapa.index')->middleware('permission:mapa.ver');
    Route::get('/mapa/data', [MapaController::class,'data'])->name('mapa.data')->middleware('permission:mapa.ver');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class,'index'])->name('index')->middleware('permission:settings.ver');

        Route::get('/usuarios', [UserController::class,'index'])->name('usuarios.index')->middleware('permission:usuarios.ver');
        Route::get('/usuarios/create', [UserController::class,'create'])->name('usuarios.create')->middleware('permission:usuarios.crear');
        Route::post('/usuarios', [UserController::class,'store'])->name('usuarios.store')->middleware('permission:usuarios.crear');
        Route::get('/usuarios/{user}', [UserController::class,'show'])->name('usuarios.show')->middleware('permission:usuarios.ver');
        Route::get('/usuarios/{user}/edit', [UserController::class,'edit'])->name('usuarios.edit')->middleware('permission:usuarios.editar');
        Route::put('/usuarios/{user}', [UserController::class,'update'])->name('usuarios.update')->middleware('permission:usuarios.editar');
        Route::delete('/usuarios/{user}', [UserController::class,'destroy'])->name('usuarios.destroy')->middleware('permission:usuarios.borrar');

        Route::get('/roles', [RoleController::class,'index'])->name('roles.index')->middleware('permission:roles.ver');
        Route::get('/roles/create', [RoleController::class,'create'])->name('roles.create')->middleware('permission:roles.crear');
        Route::post('/roles', [RoleController::class,'store'])->name('roles.store')->middleware('permission:roles.crear');
        Route::get('/roles/{role}', [RoleController::class,'show'])->name('roles.show')->middleware('permission:roles.ver');
        Route::get('/roles/{role}/edit', [RoleController::class,'edit'])->name('roles.edit')->middleware('permission:roles.editar');
        Route::put('/roles/{role}', [RoleController::class,'update'])->name('roles.update')->middleware('permission:roles.editar');
        Route::delete('/roles/{role}', [RoleController::class,'destroy'])->name('roles.destroy')->middleware('permission:roles.borrar');

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/{role}/permisos', [RolePermissionController::class,'show'])->name('permisos.show')->middleware('permission:permisos.ver');
            Route::get('/{role}/permisos/edit', [RolePermissionController::class,'edit'])->name('permisos.edit')->middleware('permission:permisos.editar');
            Route::put('/{role}/permisos', [RolePermissionController::class,'update'])->name('permisos.update')->middleware('permission:permisos.editar');
        });

        Route::get('/comunicados', [ComunicadoController::class,'index'])->name('comunicados.index')->middleware('permission:comunicados.ver');
        Route::get('/comunicados/create', [ComunicadoController::class,'create'])->name('comunicados.create')->middleware('permission:comunicados.crear');
        Route::post('/comunicados', [ComunicadoController::class,'store'])->name('comunicados.store')->middleware('permission:comunicados.crear');
        Route::get('/comunicados/{comunicado}', [ComunicadoController::class,'show'])->name('comunicados.show')->middleware('permission:comunicados.ver');
        Route::get('/comunicados/{comunicado}/edit', [ComunicadoController::class,'edit'])->name('comunicados.edit')->middleware('permission:comunicados.editar');
        Route::put('/comunicados/{comunicado}', [ComunicadoController::class,'update'])->name('comunicados.update')->middleware('permission:comunicados.editar');
        Route::delete('/comunicados/{comunicado}', [ComunicadoController::class,'destroy'])->name('comunicados.destroy')->middleware('permission:comunicados.borrar');
        Route::post('/comunicados/{comunicado}/leido', [ComunicadoController::class,'marcarLeido'])->name('comunicados.leido')->middleware('permission:comunicados.ver');

        Route::get('/app', [AppSettingController::class,'edit'])->name('app.edit')->middleware('permission:settings.editar');
        Route::put('/app', [AppSettingController::class,'update'])->name('app.update')->middleware('permission:settings.editar');
    });
});
