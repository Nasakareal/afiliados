<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BrigadaController;
use App\Http\Controllers\BrigadaParticipanteController;
Route::middleware(['auth','system.access'])->group(function () {
    Route::get('/brigadas',[BrigadaController::class,'index'])->name('brigadas.index')->middleware('permission:brigadas.ver');
    Route::get('/brigadas/export.xlsx',[BrigadaController::class,'export'])->name('brigadas.export')->middleware('permission:brigadas.ver');
    Route::get('/brigadas/create',[BrigadaController::class,'create'])->name('brigadas.create')->middleware('permission:brigadas.crear');
    Route::post('/brigadas',[BrigadaController::class,'store'])->name('brigadas.store')->middleware('permission:brigadas.crear');
    Route::get('/brigadas/{brigada}',[BrigadaController::class,'show'])->whereNumber('brigada')->name('brigadas.show')->middleware('permission:brigadas.ver');
    Route::get('/brigadas/{brigada}/edit',[BrigadaController::class,'edit'])->whereNumber('brigada')->name('brigadas.edit')->middleware('permission:brigadas.editar');
    Route::put('/brigadas/{brigada}',[BrigadaController::class,'update'])->whereNumber('brigada')->name('brigadas.update')->middleware('permission:brigadas.editar');
    Route::delete('/brigadas/{brigada}',[BrigadaController::class,'destroy'])->whereNumber('brigada')->name('brigadas.destroy')->middleware('permission:brigadas.borrar');
    Route::post('/brigadas/{brigada}/participar', [BrigadaParticipanteController::class, 'store'])->whereNumber('brigada')->name('brigadas.participar')->middleware('permission:brigadas.participar');
    Route::get('/brigadas/{brigada}/participantes/{participante}/evidencia', [BrigadaParticipanteController::class, 'evidencia'])->whereNumber(['brigada', 'participante'])->name('brigadas.participantes.evidencia')->middleware('permission:brigadas.ver');
});
