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
if (file_exists(base_path('routes/auth.php'))) { require __DIR__.'/auth.php'; }

Route::middleware('auth')->group(function () {
    Route::get('/password/force', [ForcePasswordController::class, 'form'])->name('password.force.form');
    Route::post('/password/force', [ForcePasswordController::class, 'update'])->name('password.force.update');
});

Route::middleware(['auth','force.password.change'])->group(function () {

    Route::get('/dashboard', [DashboardController::class,'index'])->name('dashboard');

    // Afiliados (CRUD)
    Route::get('/afiliados', [AfiliadoController::class, 'index'])->name('afiliados.index')->middleware('permission:afiliados.ver');
    Route::get('/afiliados/create', [AfiliadoController::class, 'create'])->name('afiliados.create')->middleware('permission:afiliados.crear');
    Route::post('/afiliados', [AfiliadoController::class, 'store'])->name('afiliados.store')->middleware('permission:afiliados.crear');
    Route::get('/afiliados/{afiliado}', [AfiliadoController::class, 'show'])->name('afiliados.show')->middleware('permission:afiliados.ver');
    Route::get('/afiliados/{afiliado}/edit', [AfiliadoController::class, 'edit'])->name('afiliados.edit')->middleware('permission:afiliados.editar');
    Route::put('/afiliados/{afiliado}', [AfiliadoController::class, 'update'])->name('afiliados.update')->middleware('permission:afiliados.editar');
    Route::delete('/afiliados/{afiliado}', [AfiliadoController::class, 'destroy'])->name('afiliados.destroy')->middleware('permission:afiliados.borrar');
    Route::get('/registro', [AfiliadoController::class, 'create'])->name('registro')->middleware('permission:afiliados.crear');

    // Secciones (CRUD)
    Route::get('/secciones', [SeccionController::class, 'index'])->name('secciones.index')->middleware('permission:secciones.ver');
    Route::get('/secciones/create', [SeccionController::class, 'create'])->name('secciones.create')->middleware('permission:secciones.crear');
    Route::post('/secciones', [SeccionController::class, 'store'])->name('secciones.store')->middleware('permission:secciones.crear');
    Route::get('/secciones/{seccion}', [SeccionController::class, 'show'])->name('secciones.show')->middleware('permission:secciones.ver');
    Route::get('/secciones/{seccion}/edit', [SeccionController::class, 'edit'])->name('secciones.edit')->middleware('permission:secciones.editar');
    Route::put('/secciones/{seccion}', [SeccionController::class, 'update'])->name('secciones.update')->middleware('permission:secciones.editar');
    Route::delete('/secciones/{seccion}', [SeccionController::class, 'destroy'])->name('secciones.destroy')->middleware('permission:secciones.borrar');

    // Actividades / Calendario
    Route::get('/calendario', [ActividadController::class, 'index'])->name('calendario.index')->middleware('permission:actividades.ver');
    Route::get('/actividades/feed', [ActividadController::class, 'feed'])->name('actividades.feed')->middleware('permission:actividades.ver');
    Route::get('/actividades', [ActividadController::class, 'list'])->name('actividades.index')->middleware('permission:actividades.ver');
    Route::get('/actividades/create', [ActividadController::class, 'create'])->name('actividades.create')->middleware('permission:actividades.crear');
    Route::post('/actividades', [ActividadController::class, 'store'])->name('actividades.store')->middleware('permission:actividades.crear');
    Route::get('/actividades/{actividad}', [ActividadController::class, 'show'])->name('actividades.show')->middleware('permission:actividades.ver');
    Route::get('/actividades/{actividad}/edit', [ActividadController::class, 'edit'])->name('actividades.edit')->middleware('permission:actividades.editar');
    Route::put('/actividades/{actividad}', [ActividadController::class, 'update'])->name('actividades.update')->middleware('permission:actividades.editar');
    Route::delete('/actividades/{actividad}', [ActividadController::class, 'destroy'])->name('actividades.destroy')->middleware('permission:actividades.borrar');

    // ======================= Reportes =======================
    Route::prefix('reportes')->name('reportes.')->middleware('permission:reportes.ver')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');

        // Afiliados (estos nombres SON los que usan tus blades)
        Route::get('/afiliados', [ReporteController::class, 'afiliados'])->name('afiliados');
        Route::get('/afiliados/data', [ReporteController::class, 'afiliadosData'])->name('afiliados.data');
        Route::get('/afiliados/export/xlsx', [ReporteController::class, 'afiliadosExportXlsx'])->name('afiliados.export.xlsx');
        Route::get('/afiliados/facets', [ReporteController::class, 'facets'])->name('afiliados.facets');

        // Secciones
        Route::get('/secciones', [ReporteController::class, 'secciones'])->name('secciones');
        Route::get('/secciones/data', [ReporteController::class, 'seccionesData'])->name('secciones.data');
        Route::get('/secciones/export/xlsx', [ReporteController::class, 'seccionesExportXlsx'])->name('secciones.export.xlsx');

        // Capturistas
        Route::get('/capturistas', [ReporteController::class, 'capturistas'])->name('capturistas');
        Route::get('/capturistas/data', [ReporteController::class, 'capturistasData'])->name('capturistas.data');
        Route::get('/capturistas/export/xlsx', [ReporteController::class, 'capturistasExportXlsx'])->name('capturistas.export.xlsx');
    });
    // ===================== /Reportes ========================

    // Mapa
    Route::get('/mapa', [MapaController::class, 'index'])->name('mapa.index')->middleware('permission:mapa.ver');
    Route::get('/mapa/data', [MapaController::class, 'data'])->name('mapa.data')->middleware('permission:mapa.ver');
});
