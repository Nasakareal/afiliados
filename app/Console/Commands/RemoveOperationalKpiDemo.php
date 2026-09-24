<?php

namespace App\Console\Commands;

use App\Support\DemoOperationalKpis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RemoveOperationalKpiDemo extends Command
{
    protected $signature = 'demo:kpis:remove {--force : Permite eliminar la demostración en producción}';

    protected $description = 'Elimina exclusivamente los datos de ejemplo del tablero KPI';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('En producción debes confirmar la eliminación con --force.');
            return self::FAILURE;
        }

        try {
            $counts = DB::transaction(function (): array {
                $audit = DB::table('auditoria_cambios')->where('demo_batch', DemoOperationalKpis::MARKER)->delete();
                $affiliates = DB::table('afiliados')->where('demo_batch', DemoOperationalKpis::MARKER)->delete();
                $activities = DB::table('actividades')->where('demo_batch', DemoOperationalKpis::MARKER)->delete();
                $users = DB::table('users')->where('demo_batch', DemoOperationalKpis::MARKER)->delete();
                return compact('audit', 'affiliates', 'activities', 'users');
            });
        } catch (Throwable $exception) {
            $this->error('No se pudo retirar la demostración: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->info("Demo KPI eliminada: {$counts['affiliates']} registros, {$counts['activities']} actividades, {$counts['users']} usuarios y {$counts['audit']} eventos de auditoría.");
        return self::SUCCESS;
    }
}
