<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RepairElectoralSectionData extends Command
{
    protected $signature = 'electoral:repair-sections
        {--apply : Guarda los cambios; sin esta opción sólo muestra el diagnóstico}
        {--overwrite : También corrige valores existentes que difieren de la cartografía oficial}
        {--source= : Ruta del GeoJSON oficial de secciones}';

    protected $description = 'Repara municipio y distritos de afiliados y distritos del catálogo de secciones';

    public function handle(): int
    {
        $source = $this->option('source') ?: public_path('maps/out/SECCION.geojson');

        try {
            $official = $this->loadOfficialSections($source);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $overwrite = (bool) $this->option('overwrite');
        $stats = [
            'catalogo_detectados' => 0,
            'catalogo_reparados' => 0,
            'afiliados_detectados' => 0,
            'afiliados_reparados' => 0,
            'sin_fuente_oficial' => 0,
            'sin_catalogo_unico' => 0,
        ];

        $work = function () use ($official, $apply, $overwrite, &$stats): void {
            DB::table('secciones')
                ->select('id', 'seccion', 'distrito_local', 'distrito_federal')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($official, $apply, $overwrite, &$stats): void {
                    foreach ($rows as $row) {
                        $key = $this->sectionKey($row->seccion);
                        $source = $official[$key] ?? null;

                        if (!$source) {
                            if ($row->distrito_local === null || $row->distrito_federal === null) {
                                $stats['sin_fuente_oficial']++;
                            }
                            continue;
                        }

                        $changes = $this->districtChanges($row, $source, $overwrite);
                        if (!$changes) {
                            continue;
                        }

                        $stats['catalogo_detectados']++;
                        if ($apply) {
                            DB::table('secciones')->where('id', $row->id)->update($changes);
                            $stats['catalogo_reparados']++;
                        }
                    }
                }, 'id');

            $catalog = $this->uniqueCatalogBySection();

            DB::table('afiliados')
                ->select(
                    'id', 'seccion', 'municipio', 'cve_mun',
                    'distrito_local', 'distrito_federal'
                )
                ->whereNotNull('seccion')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($official, $catalog, $apply, $overwrite, &$stats): void {
                    foreach ($rows as $row) {
                        $key = $this->sectionKey($row->seccion);
                        $source = $official[$key] ?? null;
                        $catalogRow = $catalog[$key] ?? null;

                        $changes = [];
                        if ($source) {
                            $changes = $this->districtChanges($row, $source, $overwrite);
                        }

                        if ($catalogRow) {
                            $this->addChange($changes, 'municipio', $row->municipio, $catalogRow->municipio, $overwrite);
                            $this->addChange($changes, 'cve_mun', $row->cve_mun, $catalogRow->cve_mun, $overwrite);
                        } elseif ($this->isBlank($row->municipio) || $this->isBlank($row->cve_mun)) {
                            $stats['sin_catalogo_unico']++;
                        }

                        if (!$changes) {
                            continue;
                        }

                        $stats['afiliados_detectados']++;
                        if ($apply) {
                            DB::table('afiliados')->where('id', $row->id)->update($changes);
                            $stats['afiliados_reparados']++;
                        }
                    }
                }, 'id');
        };

        if ($apply) {
            DB::transaction($work);
        } else {
            $work();
        }

        $this->table(['Resultado', 'Cantidad'], [
            ['Secciones de catálogo por reparar', $stats['catalogo_detectados']],
            ['Secciones de catálogo reparadas', $stats['catalogo_reparados']],
            ['Afiliados por reparar', $stats['afiliados_detectados']],
            ['Afiliados reparados', $stats['afiliados_reparados']],
            ['Secciones faltantes en GeoJSON', $stats['sin_fuente_oficial']],
            ['Afiliados sin catálogo único', $stats['sin_catalogo_unico']],
        ]);

        if (!$apply) {
            $this->warn('Diagnóstico solamente. Ejecuta de nuevo con --apply para guardar.');
        } else {
            $this->info('Reparación terminada correctamente.');
        }

        return self::SUCCESS;
    }

    private function loadOfficialSections(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("No se puede leer el GeoJSON oficial: {$path}");
        }

        $sections = [];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("No se pudo abrir el GeoJSON oficial: {$path}");
        }

        while (($line = fgets($handle)) !== false) {
            if (!preg_match('/"properties"\s*:\s*(\{[^{}]*\})\s*\}\s*,?\s*$/', $line, $match)) {
                continue;
            }

            $properties = json_decode($match[1], true);
            if (!is_array($properties)) {
                fclose($handle);
                throw new RuntimeException("Hay propiedades inválidas en el GeoJSON oficial: {$path}");
            }

            $key = $this->sectionKey($properties['SECCION'] ?? null);
            $local = $this->positiveInt($properties['DISTRITO_L'] ?? null);
            $federal = $this->positiveInt($properties['DISTRITO_F'] ?? null);

            if ($key === '' || $local === null || $federal === null) {
                continue;
            }

            $candidate = [
                'distrito_local' => $local,
                'distrito_federal' => $federal,
            ];

            if (isset($sections[$key]) && $sections[$key] !== $candidate) {
                fclose($handle);
                throw new RuntimeException("La sección {$key} tiene distritos contradictorios en el GeoJSON.");
            }

            $sections[$key] = $candidate;
        }

        fclose($handle);

        if (!$sections) {
            throw new RuntimeException("El GeoJSON no contiene secciones con distritos válidos: {$path}");
        }

        return $sections;
    }

    private function uniqueCatalogBySection(): array
    {
        $unique = [];
        $ambiguous = [];

        DB::table('secciones')
            ->select('seccion', 'municipio', 'cve_mun')
            ->orderBy('id')
            ->get()
            ->each(function ($row) use (&$unique, &$ambiguous): void {
                $key = $this->sectionKey($row->seccion);
                if ($key === '' || isset($ambiguous[$key])) {
                    return;
                }

                if (isset($unique[$key])) {
                    $previous = $unique[$key];
                    if ($previous->municipio !== $row->municipio || (string) $previous->cve_mun !== (string) $row->cve_mun) {
                        unset($unique[$key]);
                        $ambiguous[$key] = true;
                    }
                    return;
                }

                $unique[$key] = $row;
            });

        return $unique;
    }

    private function districtChanges(object $row, array $source, bool $overwrite): array
    {
        $changes = [];
        $this->addChange($changes, 'distrito_local', $row->distrito_local, $source['distrito_local'], $overwrite);
        $this->addChange($changes, 'distrito_federal', $row->distrito_federal, $source['distrito_federal'], $overwrite);
        return $changes;
    }

    private function addChange(array &$changes, string $field, $current, $correct, bool $overwrite): void
    {
        if ($correct === null || $correct === '') {
            return;
        }

        if ($this->isBlank($current) || ($overwrite && (string) $current !== (string) $correct)) {
            $changes[$field] = $correct;
        }
    }

    private function isBlank($value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function sectionKey($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value)) {
            return $value;
        }

        return (string) ((int) $value);
    }

    private function positiveInt($value): ?int
    {
        if (!is_numeric($value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }
}
