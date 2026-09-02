<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\DemoTablaAparte;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class LoadTablaAparteDemo extends Command
{
    protected $signature = 'demo:tabla-aparte:load
        {--capturista-email= : Correo del usuario que figurará como capturista}
        {--force : Permite ejecutar la carga en producción}';

    protected $description = 'Carga el lote temporal tablaaparte para la demostración del sistema';

    public function handle(): int
    {
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
        if (function_exists('ini_set')) {
            ini_set('memory_limit', '512M');
        }

        if (app()->environment('production') && !$this->option('force')) {
            $this->error('En producción debes confirmar la carga con --force.');
            return self::FAILURE;
        }

        try {
            $capturista = $this->resolveCapturista();
            [$sections, $rowCount] = $this->scanData();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        DB::connection()->disableQueryLog();
        $now = now();
        $inserted = 0;
        try {
            DB::transaction(function () use ($capturista, $sections, $now, $rowCount, &$inserted): void {
                DB::table('afiliados')
                    ->where(function ($query): void {
                        $query->where('demo_batch', DemoTablaAparte::MARKER)
                            ->orWhere('observaciones', 'like', DemoTablaAparte::MARKER.'%');
                    })
                    ->delete();

                foreach (array_chunk(array_values($sections), 500) as $chunk) {
                    DB::table('secciones')->insertOrIgnore($chunk);
                }

                $sectionCatalog = $this->sectionCatalog($sections);

                $batch = [];
                foreach (DemoTablaAparte::rows() as $row) {
                    $geography = $sectionCatalog[(int) $row['seccion']];
                    $batch[] = [
                        'capturista_id' => $capturista->id,
                        'nombre' => $row['nombre'],
                        'apellido_paterno' => $this->nullable($row['apellido_paterno']),
                        'apellido_materno' => $this->nullable($row['apellido_materno']),
                        'telefono' => $this->nullable($row['telefono']),
                        'municipio' => $geography['municipio'],
                        'cve_mun' => $geography['cve_mun'],
                        'colonia' => $this->nullable($row['colonia']),
                        'calle' => $this->nullable($row['calle']),
                        'numero_ext' => $this->nullable($row['numero_ext']),
                        'numero_int' => $this->nullable($row['numero_int']),
                        'cp' => $this->nullable($row['cp']),
                        'seccion' => $geography['seccion'],
                        'distrito_federal' => $geography['distrito_federal'],
                        'distrito_local' => $geography['distrito_local'],
                        'perfil' => DemoTablaAparte::REFERENTE,
                        'observaciones' => 'Carga temporal para demostración del sistema.',
                        'demo_batch' => DemoTablaAparte::MARKER,
                        'estatus' => 'validado',
                        'fecha_convencimiento' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => $now,
                    ];

                    if (count($batch) === 500) {
                        DB::table('afiliados')->insert($batch);
                        $inserted += count($batch);
                        $batch = [];
                    }
                }

                if ($batch) {
                    DB::table('afiliados')->insert($batch);
                    $inserted += count($batch);
                }

                $stored = DB::table('afiliados')
                    ->where('demo_batch', DemoTablaAparte::MARKER)
                    ->count();

                if ($inserted !== $rowCount || $stored !== $rowCount) {
                    throw new RuntimeException("La carga quedó incompleta: {$stored} de {$rowCount} registros.");
                }

                DB::table('afiliados')
                    ->where('demo_batch', DemoTablaAparte::MARKER)
                    ->update(['deleted_at' => null]);
            });
        } catch (Throwable $exception) {
            DB::table('afiliados')
                ->where(function ($query): void {
                    $query->where('demo_batch', DemoTablaAparte::MARKER)
                        ->orWhere('observaciones', 'like', DemoTablaAparte::MARKER.'%');
                })
                ->delete();

            $this->error('No se pudo cargar la demo: '.$exception->getMessage());
            return self::FAILURE;
        }

        if ($inserted !== $rowCount) {
            $this->error("La carga terminó con {$inserted} de {$rowCount} registros.");
            return self::FAILURE;
        }

        $this->info("Demo cargada: {$inserted} afiliados asignados a ".DemoTablaAparte::REFERENTE.'.');
        $this->line("Capturista: {$capturista->name} <{$capturista->email}>");

        return self::SUCCESS;
    }

    private function resolveCapturista(): User
    {
        $email = trim((string) ($this->option('capturista-email') ?: env('DEMO_CAPTURISTA_EMAIL')));

        if ($email !== '') {
            $user = User::query()->where('email', $email)->first();
            if (!$user) {
                throw new RuntimeException("No existe el capturista con correo {$email}.");
            }
            return $user;
        }

        $user = User::role('SuperAdmin')->orderBy('id')->first()
            ?: User::role('Admin')->orderBy('id')->first();

        if (!$user) {
            throw new RuntimeException('No existe un usuario SuperAdmin o Admin para registrar la carga.');
        }

        return $user;
    }

    private function scanData(): array
    {
        $sections = [];
        $rowCount = 0;
        $now = now();

        foreach (DemoTablaAparte::rows() as $row) {
            $rowCount++;
            $key = $row['cve_mun'].'|'.$row['seccion'];
            $sections[$key] = [
                'cve_ent' => '16',
                'cve_mun' => $row['cve_mun'],
                'municipio' => $row['municipio'],
                'seccion' => $row['seccion'],
                'distrito_federal' => (int) $row['distrito_federal'],
                'distrito_local' => (int) $row['distrito_local'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rowCount !== DemoTablaAparte::EXPECTED_ROWS) {
            throw new RuntimeException("El lote contiene {$rowCount} filas; se esperaban ".DemoTablaAparte::EXPECTED_ROWS.'.');
        }

        return [$sections, $rowCount];
    }

    private function sectionCatalog(array $expectedSections): array
    {
        $catalog = [];

        DB::table('secciones')
            ->select('cve_mun', 'municipio', 'seccion', 'distrito_federal', 'distrito_local')
            ->orderBy('id')
            ->get()
            ->each(function ($section) use (&$catalog): void {
                $key = (int) $section->seccion;
                $candidate = [
                    'cve_mun' => (string) $section->cve_mun,
                    'municipio' => (string) $section->municipio,
                    'seccion' => (string) $section->seccion,
                    'distrito_federal' => (int) $section->distrito_federal,
                    'distrito_local' => (int) $section->distrito_local,
                ];

                if (isset($catalog[$key]) && $catalog[$key] !== $candidate) {
                    throw new RuntimeException("La sección {$section->seccion} es ambigua en el catálogo.");
                }

                $catalog[$key] = $candidate;
            });

        foreach ($expectedSections as $expected) {
            $key = (int) $expected['seccion'];
            if (!isset($catalog[$key])) {
                throw new RuntimeException("La sección {$expected['seccion']} no pudo incorporarse al catálogo.");
            }
        }

        return $catalog;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);
        return $value !== '' ? $value : null;
    }
}
