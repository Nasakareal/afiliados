<?php

namespace App\Console\Commands;

use App\Support\DemoTablaAparte;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveTablaAparteDemo extends Command
{
    protected $signature = 'demo:tabla-aparte:remove
        {--force : Permite eliminar el lote en producción}';

    protected $description = 'Elimina exclusivamente el lote temporal tablaaparte';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('En producción debes confirmar la eliminación con --force.');
            return self::FAILURE;
        }

        $deleted = DB::table('afiliados')
            ->where(function ($query): void {
                $query->where('demo_batch', DemoTablaAparte::MARKER)
                    ->orWhere('observaciones', 'like', DemoTablaAparte::MARKER.'%');
            })
            ->delete();

        $this->info("Demo eliminada: {$deleted} afiliados retirados.");
        return self::SUCCESS;
    }
}
