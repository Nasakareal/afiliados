<?php

namespace App\Console\Commands;

use App\Support\DemoOperationalKpis;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Throwable;

class LoadOperationalKpiDemo extends Command
{
    protected $signature = 'demo:kpis:load {--force : Permite cargar la demostración en producción}';

    protected $description = 'Carga datos identificados para demostrar el tablero de KPI operativos';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('En producción debes confirmar la carga con --force.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function (): void {
                $this->removeExisting();
                $users = $this->createUsers();
                $activities = $this->createActivities($users);
                $this->createParticipants($users, $activities);
                $this->createAffiliates($users, $activities);
                $this->createAudit($users, $activities);
            });
        } catch (Throwable $exception) {
            $this->error('No se pudo cargar la demostración: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Demo KPI cargada: 4 responsables, 12 actividades y 192 registros.');
        $this->line('Para retirarla: php artisan demo:kpis:remove');
        return self::SUCCESS;
    }

    private function createUsers(): array
    {
        Role::firstOrCreate(['name' => 'Coordinador', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Capturista', 'guard_name' => 'web']);

        $names = ['Ana Torres', 'Carlos Mendoza', 'María López', 'Pedro Ramírez'];
        $users = [];
        foreach (DemoOperationalKpis::USER_EMAILS as $index => $email) {
            $id = DB::table('users')->insertGetId([
                'name' => $names[$index],
                'email' => $email,
                'password' => Hash::make('DemoKpi2026!'),
                'email_verified_at' => now(),
                'must_change_password' => false,
                'distrito_local' => null,
                'demo_batch' => DemoOperationalKpis::MARKER,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $users[] = $id;

            $roleId = Role::where('name', $index < 2 ? 'Coordinador' : 'Capturista')
                ->where('guard_name', 'web')->value('id');
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $id,
            ]);
        }
        return $users;
    }

    private function createActivities(array $users): array
    {
        $definitions = [
            ['Brigada informativa', 'Recorrido', 'Ventura Puente', -34, 'realizada', 80, true, 0],
            ['Encuentro vecinal', 'Reunión', 'Centro', -28, 'realizada', 55, true, 1],
            ['Módulo de atención', 'Módulo', 'Chapultepec Norte', -21, 'realizada', 120, true, 2],
            ['Jornada comunitaria', 'Jornada', 'Felícitas del Río', -17, 'realizada', 95, false, 3],
            ['Taller de capacitación', 'Taller', 'Industrial', -12, 'realizada', 42, true, 0],
            ['Visita territorial', 'Recorrido', 'Nueva Chapultepec', -8, 'realizada', 35, true, 1],
            ['Asamblea de seguimiento', 'Reunión', 'Ventura Puente', -5, 'realizada', 140, true, 2],
            ['Módulo itinerante', 'Módulo', 'Centro', -2, 'realizada', 38, false, 3],
            ['Reunión de coordinación', 'Planeación', 'Chapultepec Norte', 2, 'programada', null, false, 0],
            ['Jornada de servicios', 'Jornada', 'Felícitas del Río', 5, 'programada', null, false, 1],
            ['Capacitación de enlaces', 'Taller', 'Industrial', 8, 'programada', null, false, 2],
            ['Recorrido cancelado', 'Recorrido', 'Nueva Chapultepec', 11, 'cancelada', null, false, 3],
        ];

        $ids = [];
        foreach ($definitions as $index => [$title, $type, $colony, $days, $state, $attendance, $evidence, $owner]) {
            $start = now()->addDays($days)->setTime(10 + ($index % 5), 0);
            $ids[] = DB::table('actividades')->insertGetId([
                'titulo' => $title,
                'tipo' => $type,
                'origen' => $index >= 8 ? 'oficial' : 'reporte_usuario',
                'descripcion' => 'Registro demostrativo del tablero operativo.',
                'inicio' => $start,
                'fin' => $start->copy()->addHours(2),
                'all_day' => false,
                'lugar' => 'Punto comunitario '.$colony,
                'colonia' => $colony,
                'creado_por' => $users[0],
                'responsable_id' => $users[$owner],
                'capturista_id' => $users[$owner],
                'distrito_local' => 17,
                'asistentes' => $attendance,
                'evidencia_url' => $evidence ? 'https://example.invalid/evidencia/demo-'.($index + 1) : null,
                'capturada_en' => $state === 'realizada' ? $start->copy()->addHours($index % 3 === 0 ? 30 : 3) : null,
                'demo_batch' => DemoOperationalKpis::MARKER,
                'estado' => $state,
                'estado_revision' => $index >= 8 || $index % 3 !== 0 ? 'aprobada' : 'pendiente',
                'created_at' => $start->copy()->subDays(3),
                'updated_at' => now(),
            ]);
        }
        return $ids;
    }

    private function createParticipants(array $users, array $activities): void
    {
        foreach ($activities as $index => $activityId) {
            DB::table('actividad_participantes')->insert([
                'actividad_id' => $activityId,
                'user_id' => $users[$index % count($users)],
                'asistio_en' => now()->subDays(max(0, 8 - $index)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($index % 2 === 0) {
                DB::table('actividad_participantes')->insert([
                    'actividad_id' => $activityId,
                    'user_id' => $users[($index + 1) % count($users)],
                    'asistio_en' => now()->subDays(max(0, 8 - $index)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function createAffiliates(array $users, array $activities): void
    {
        $colonies = ['Ventura Puente', 'Centro', 'Chapultepec Norte', 'Felícitas del Río', 'Industrial', 'Nueva Chapultepec'];
        $centers = [
            [19.6906, -101.1815], [19.7020, -101.1924], [19.6898, -101.1734],
            [19.6813, -101.2045], [19.7148, -101.1911], [19.6871, -101.1662],
        ];
        $firstNames = ['Sofía', 'Diego', 'Valeria', 'Jorge', 'Lucía', 'Mateo', 'Elena', 'Raúl'];
        $lastNames = ['García', 'Hernández', 'Martínez', 'Sánchez', 'Flores', 'Reyes'];
        $rows = [];

        for ($i = 1; $i <= 192; $i++) {
            $owner = ($i - 1) % 4;
            $colonyIndex = ($i - 1) % count($colonies);
            $activityIndex = ($i - 1) % 8;
            $incomplete = $i % 11 === 0;
            $duplicate = $i % 17 === 0;
            $created = now()->subDays(($i * 3) % 38)->setTime(9 + ($i % 9), $i % 60);

            $rows[] = [
                'capturista_id' => $users[$owner],
                'actividad_id' => $i % 13 === 0 ? null : $activities[$activityIndex],
                'nombre' => $firstNames[$i % count($firstNames)],
                'apellido_paterno' => $lastNames[$i % count($lastNames)],
                'apellido_materno' => $lastNames[($i + 2) % count($lastNames)],
                'edad' => 18 + ($i % 58),
                'sexo' => $i % 2 ? 'F' : 'M',
                'telefono' => $incomplete ? null : ($duplicate ? '4435550017' : '443'.str_pad((string) (7000000 + $i), 7, '0', STR_PAD_LEFT)),
                'email' => $incomplete ? null : 'persona'.$i.'@demo.invalid',
                'municipio' => 'Morelia',
                'cve_mun' => '053',
                'localidad' => 'Morelia',
                'colonia' => $incomplete && $i % 22 === 0 ? null : $colonies[$colonyIndex],
                'calle' => 'Calle demostración '.(($i % 20) + 1),
                'numero_ext' => (string) (($i % 150) + 1),
                'cp' => '58'.str_pad((string) (100 + $colonyIndex), 3, '0', STR_PAD_LEFT),
                'lat' => $centers[$colonyIndex][0] + (($i % 7) - 3) * .0007,
                'lng' => $centers[$colonyIndex][1] + (($i % 5) - 2) * .0007,
                'seccion' => $incomplete ? null : (string) (1100 + $colonyIndex),
                'distrito_federal' => 8,
                'distrito_local' => 17,
                'perfil' => 'Registro de demostración KPI',
                'observaciones' => 'Dato de ejemplo; puede eliminarse con demo:kpis:remove.',
                'demo_batch' => DemoOperationalKpis::MARKER,
                'estatus' => $i % 14 === 0 ? 'descartado' : 'validado',
                'fecha_convencimiento' => $created,
                'created_at' => $created,
                'updated_at' => $created,
                'deleted_at' => null,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('afiliados')->insert($chunk);
        }
    }

    private function createAudit(array $users, array $activities): void
    {
        $affiliates = DB::table('afiliados')->where('demo_batch', DemoOperationalKpis::MARKER)
            ->orderBy('id')->limit(4)->pluck('id')->values();
        $actions = [
            ['actualizado', 'Teléfono corregido en registro demostrativo'],
            ['actualizado', 'Colonia actualizada y sección verificada'],
            ['creado', 'Actividad registrada con evidencia'],
            ['reasignado', 'Responsable de actividad reasignado'],
            ['actualizado', 'Estado del registro validado'],
            ['eliminado', 'Registro duplicado retirado de la base'],
        ];

        foreach ($actions as $index => [$action, $summary]) {
            DB::table('auditoria_cambios')->insert([
                'usuario_id' => $users[$index % 4],
                'entidad' => $index === 2 || $index === 3 ? 'actividad' : 'afiliado',
                'entidad_id' => $index === 2 || $index === 3
                    ? $activities[$index % count($activities)]
                    : $affiliates[$index % $affiliates->count()],
                'accion' => $action,
                'resumen' => $summary,
                'cambios' => json_encode(['demo' => true]),
                'demo_batch' => DemoOperationalKpis::MARKER,
                'created_at' => now()->subHours($index * 5 + 1),
                'updated_at' => now()->subHours($index * 5 + 1),
            ]);
        }
    }

    private function removeExisting(): void
    {
        DB::table('auditoria_cambios')->where('demo_batch', DemoOperationalKpis::MARKER)->delete();
        DB::table('afiliados')->where('demo_batch', DemoOperationalKpis::MARKER)->delete();
        DB::table('actividades')->where('demo_batch', DemoOperationalKpis::MARKER)->delete();
        DB::table('users')->where('demo_batch', DemoOperationalKpis::MARKER)->delete();
    }
}
