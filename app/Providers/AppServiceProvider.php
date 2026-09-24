<?php

namespace App\Providers;

use App\Models\Actividad;
use App\Models\Afiliado;
use App\Models\AuditoriaCambio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (method_exists(Paginator::class,'useBootstrapFive')) {
            Paginator::useBootstrapFive();
        } elseif (method_exists(Paginator::class,'useBootstrapFour')) {
            Paginator::useBootstrapFour();
        } else {
            Paginator::useBootstrap();
        }

        foreach ([Afiliado::class => 'afiliado', Actividad::class => 'actividad'] as $model => $entity) {
            $model::created(fn (Model $record) => $this->audit($record, $entity, 'creado'));
            $model::updated(fn (Model $record) => $this->audit($record, $entity, 'actualizado'));
            $model::deleted(fn (Model $record) => $this->audit($record, $entity, 'eliminado'));
        }
    }

    private function audit(Model $record, string $entity, string $action): void
    {
        if (!Schema::hasTable('auditoria_cambios')) {
            return;
        }

        $changed = $action === 'actualizado'
            ? array_values(array_diff(array_keys($record->getChanges()), ['updated_at']))
            : [];
        $label = $entity === 'actividad'
            ? ($record->titulo ?? "#{$record->getKey()}")
            : trim(($record->nombre ?? '').' '.($record->apellido_paterno ?? ''));

        AuditoriaCambio::create([
            'usuario_id' => Auth::id(),
            'entidad' => $entity,
            'entidad_id' => $record->getKey(),
            'accion' => $action,
            'resumen' => ucfirst($entity).' '.$label.' '.$action,
            'cambios' => $changed ? ['campos' => $changed] : null,
        ]);
    }
}
