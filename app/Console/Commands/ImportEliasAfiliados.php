<?php

namespace App\Console\Commands;

use App\Services\AfiliadosResumenService;
use App\Services\EliasAfiliadosImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ImportEliasAfiliados extends Command
{
    private const EXPECTED_FILES = [
        '1.xlsx' => '699c5b4a76da6de5af489dcadb8557d58cacf03141b5fcd980283a885aff1f84',
        '2.xlsx' => 'a17f37a3ee4c2800a1b2f998d00927bb689f6ceb3597c33a68443c8634107936',
        '3.xlsx' => '10d9133d502b50d57a3ef45da05de090a93a44f89e7a1b97bfe7206f3d365d6e',
        '4.xlsx' => '4ff6f98900c6bf2afa6e19a8aeef73d82c36bf52ac0d28740bf213cc4757573a',
        '5.xlsx' => '2285a96a6f4840aa9bfb84dc556f70259f22fa7f1ffd962e5798a3bf43f45495',
        '6.xlsx' => '1ae914bf621dad682b160d6fd65bc6b003d5b6bd84647b16c9df14ca76811b3c',
    ];

    protected $signature = 'afiliados:importar-elias
        {directorio=storage/app/importaciones/elias : Directorio que contiene exactamente 1.xlsx a 6.xlsx}
        {--capturista-id=32 : Usuario al que se asignará la captura}
        {--referente=Elías Ibarra Torres : Referente que se guardará en perfil}
        {--lote=elias-ibarra-torres-2026-09-08-v1 : Identificador idempotente del lote}
        {--confirmar : Ejecuta la inserción; sin esta opción sólo valida y simula}
        {--force : Confirmación adicional obligatoria en producción}';

    protected $description = 'Valida e importa como afiliados formales los seis archivos de Elías';

    public function handle(
        EliasAfiliadosImportService $importer,
        AfiliadosResumenService $summaryService
    ): int {
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
        if (function_exists('ini_set')) {
            ini_set('memory_limit', '1024M');
        }
        DB::connection()->disableQueryLog();

        if (!Schema::hasColumn('afiliados', 'import_batch')) {
            $this->error('Falta la migración que agrega afiliados.import_batch. Ejecuta primero php artisan migrate --force.');
            return self::FAILURE;
        }

        if ($this->option('confirmar') && app()->environment('production') && !$this->option('force')) {
            $this->error('En producción debes agregar --force junto con --confirmar.');
            return self::FAILURE;
        }

        try {
            $directory = $this->resolveDirectory((string) $this->argument('directorio'));
            $this->verifyFiles($directory);
            $capturistaId = $this->capturistaId();
            $referente = trim((string) $this->option('referente'));
            $batchMarker = trim((string) $this->option('lote'));
            $this->validateOptions($referente, $batchMarker);
            if (DB::table('afiliados')->where('import_batch', $batchMarker)->exists()) {
                throw new RuntimeException("El lote {$batchMarker} ya fue cargado; no se insertó nada.");
            }
            $catalog = $this->sectionCatalog();

            $this->line('Leyendo y validando los seis archivos...');
            $scan = $importer->scan($directory, $catalog);
            [$newRows, $existingDuplicates, $identityDuplicates] = $this->removeExistingDuplicates($scan['rows'], $importer);
            $this->showSummary(
                $scan,
                count($newRows),
                $existingDuplicates,
                $identityDuplicates,
                $capturistaId,
                $referente,
                $batchMarker
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $report = null;
        try {
            $report = $this->writeRejectionReport($scan['rejections'], $batchMarker);
        } catch (Throwable $exception) {
            $this->warn('No se pudo escribir el reporte de filas rechazadas: '.$exception->getMessage());
        }

        if (!$this->option('confirmar')) {
            $this->newLine();
            $this->info('Simulación terminada: no se modificó la base de datos.');
            if ($report !== null) {
                $this->line("Filas no importables: {$report}");
            }
            return self::SUCCESS;
        }

        if ($newRows === []) {
            $this->error('No hay registros nuevos que insertar.');
            return self::FAILURE;
        }

        try {
            $inserted = DB::transaction(function () use (
                $newRows,
                $capturistaId,
                $referente,
                $batchMarker,
                $summaryService
            ): int {
                $capturista = DB::table('users')->where('id', $capturistaId)->lockForUpdate()->first();
                if (!$capturista) {
                    throw new RuntimeException("No existe el usuario {$capturistaId}.");
                }

                if (DB::table('afiliados')->where('import_batch', $batchMarker)->exists()) {
                    throw new RuntimeException("El lote {$batchMarker} ya fue cargado; no se insertó nada.");
                }

                $now = now();
                $inserted = 0;
                foreach (array_chunk($newRows, 500) as $chunk) {
                    $records = [];
                    foreach ($chunk as $row) {
                        $source = $row['_source_file'].' / fila '.$row['_source_row'];
                        if ($row['_source_id'] !== '') {
                            $source .= ' / id '.$row['_source_id'];
                        }
                        $phoneNote = $row['_phone_state'] === 'invalid'
                            ? ' Teléfono fuente inválido omitido.'
                            : '';

                        unset(
                            $row['_source_file'],
                            $row['_source_sheet'],
                            $row['_source_row'],
                            $row['_source_id'],
                            $row['_phone_state']
                        );

                        $records[] = array_merge($row, [
                            'capturista_id' => $capturistaId,
                            'perfil' => $referente,
                            'observaciones' => 'Carga formal de Elías. Origen: '.$source.'.'.$phoneNote,
                            'import_batch' => $batchMarker,
                            'estatus' => 'validado',
                            'fecha_convencimiento' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                            'deleted_at' => null,
                        ]);
                    }

                    DB::table('afiliados')->insert($records);
                    $inserted += count($records);
                }

                $stored = DB::table('afiliados')
                    ->where('import_batch', $batchMarker)
                    ->whereNull('deleted_at')
                    ->where('capturista_id', $capturistaId)
                    ->where('perfil', $referente)
                    ->where('estatus', 'validado')
                    ->whereNotNull('cve_mun')
                    ->whereNotNull('distrito_local')
                    ->whereNotNull('distrito_federal')
                    ->count();

                if ($stored !== $inserted) {
                    throw new RuntimeException("La verificación final encontró {$stored} de {$inserted} registros correctos.");
                }

                if (Schema::hasTable('afiliados_resumen')) {
                    $summaryService->rebuild();
                    $active = DB::table('afiliados')->whereNull('deleted_at')->count();
                    $summarized = (int) DB::table('afiliados_resumen')->sum('total');
                    if ($active !== $summarized) {
                        throw new RuntimeException("El resumen quedó en {$summarized} de {$active} afiliados activos.");
                    }
                }

                return $inserted;
            }, 3);
        } catch (Throwable $exception) {
            $this->error('La transacción se revirtió por completo: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Carga formal completada y verificada: {$inserted} afiliados nuevos.");
        if ($report !== null) {
            $this->line("Filas no importables: {$report}");
        }

        return self::SUCCESS;
    }

    private function resolveDirectory(string $directory): string
    {
        $directory = trim($directory);
        if (!preg_match('#^(?:[A-Za-z]:[\\\\/]|[\\\\/]{2}|/)#', $directory)) {
            $directory = base_path($directory);
        }
        $resolved = realpath($directory);
        if ($resolved === false || !is_dir($resolved)) {
            throw new RuntimeException("No existe el directorio de importación: {$directory}");
        }
        return $resolved;
    }

    private function verifyFiles(string $directory): void
    {
        $actual = [];
        foreach (glob($directory.DIRECTORY_SEPARATOR.'*.xlsx') ?: [] as $file) {
            $actual[basename($file)] = strtolower((string) hash_file('sha256', $file));
        }
        ksort($actual, SORT_NATURAL);

        if (array_keys($actual) !== array_keys(self::EXPECTED_FILES)) {
            throw new RuntimeException('El directorio debe contener exactamente los archivos 1.xlsx, 2.xlsx, 3.xlsx, 4.xlsx, 5.xlsx y 6.xlsx.');
        }

        foreach (self::EXPECTED_FILES as $name => $hash) {
            if (!hash_equals($hash, $actual[$name])) {
                throw new RuntimeException("El archivo {$name} no coincide con el original revisado (SHA-256 distinto).");
            }
        }
    }

    private function capturistaId(): int
    {
        $id = filter_var($this->option('capturista-id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new RuntimeException('capturista-id debe ser un entero positivo.');
        }
        if (!DB::table('users')->where('id', $id)->exists()) {
            throw new RuntimeException("No existe el usuario {$id}.");
        }
        return (int) $id;
    }

    private function validateOptions(string $referente, string $batchMarker): void
    {
        if ($referente === '' || mb_strlen($referente) > 191) {
            throw new RuntimeException('El referente es obligatorio y no puede superar 191 caracteres.');
        }
        if ($batchMarker === '' || strlen($batchMarker) > 64 || !preg_match('/^[a-z0-9._-]+$/', $batchMarker)) {
            throw new RuntimeException('El lote debe usar hasta 64 caracteres: minúsculas, números, punto, guion o guion bajo.');
        }
    }

    private function sectionCatalog(): array
    {
        $catalog = [];
        DB::table('secciones')
            ->select('cve_mun', 'municipio', 'seccion', 'distrito_local', 'distrito_federal')
            ->get()
            ->each(function ($section) use (&$catalog): void {
                $number = preg_replace('/\D+/', '', (string) $section->seccion);
                if ($number === '') {
                    return;
                }
                $number = (string) (int) $number;
                $catalog[$number][] = [
                    'cve_mun' => str_pad((string) $section->cve_mun, 3, '0', STR_PAD_LEFT),
                    'municipio' => trim((string) $section->municipio),
                    'distrito_local' => $section->distrito_local,
                    'distrito_federal' => $section->distrito_federal,
                ];
            });

        if ($catalog === []) {
            throw new RuntimeException('El catálogo de secciones está vacío.');
        }
        return $catalog;
    }

    private function removeExistingDuplicates(array $rows, EliasAfiliadosImportService $importer): array
    {
        $existing = [];
        DB::table('afiliados')
            ->whereNull('deleted_at')
            ->select(
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'telefono',
                'cve_mun',
                'seccion',
                'colonia',
                'calle',
                'numero_ext'
            )
            ->orderBy('id')
            ->chunk(2000, function ($chunk) use (&$existing, $importer): void {
                foreach ($chunk as $row) {
                    $existing[$importer->identityKey((array) $row)] = true;
                }
            });

        $new = [];
        $databaseDuplicates = 0;
        $identityDuplicates = 0;
        $inputKeys = [];
        foreach ($rows as $row) {
            $key = $importer->identityKey($row);
            if (isset($existing[$key])) {
                $databaseDuplicates++;
                continue;
            }
            if (isset($inputKeys[$key])) {
                $identityDuplicates++;
                continue;
            }
            $inputKeys[$key] = true;
            $new[] = $row;
        }
        return [$new, $databaseDuplicates, $identityDuplicates];
    }

    private function showSummary(
        array $scan,
        int $newRows,
        int $existingDuplicates,
        int $identityDuplicates,
        int $capturistaId,
        string $referente,
        string $batch
    ): void
    {
        $stats = $scan['stats'];
        $this->newLine();
        $this->table(['Control', 'Resultado'], [
            ['Archivos SHA-256 verificados', implode(', ', $scan['files'])],
            ['Filas de personas revisadas', number_format($stats['source_rows'])],
            ['Encabezados internos omitidos', number_format($stats['repeated_headers'])],
            ['Filas vacías omitidas', number_format($stats['blank_rows'])],
            ['Duplicados exactos en los Excel', number_format($stats['source_duplicates'])],
            ['Duplicados por identidad en los Excel', number_format($identityDuplicates)],
            ['Duplicados ya presentes en afiliados', number_format($existingDuplicates)],
            ['Filas no importables', number_format(count($scan['rejections']))],
            ['Teléfonos ausentes', number_format($stats['missing_phones'])],
            ['Teléfonos inválidos omitidos', number_format($stats['invalid_phones'])],
            ['Afiliados nuevos listos', number_format($newRows)],
            ['Capturista', (string) $capturistaId],
            ['Referente', $referente],
            ['Estatus', 'validado (formal y activo)'],
            ['Lote', $batch],
        ]);

        if ($scan['rejections'] !== []) {
            $examples = array_slice($scan['rejections'], 0, 12);
            $this->warn('Ejemplos de filas no importables:');
            $this->table(
                ['Archivo', 'Hoja', 'Fila', 'Motivo', 'Nombre', 'Teléfono', 'Sección'],
                array_map(fn (array $row): array => array_values($row), $examples)
            );
        }
    }

    private function writeRejectionReport(array $rejections, string $batchMarker): ?string
    {
        if ($rejections === []) {
            return null;
        }

        $directory = storage_path('app/importaciones/reportes');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("No se pudo crear el directorio de reportes {$directory}");
        }
        $path = $directory.DIRECTORY_SEPARATOR.$batchMarker.'-rechazadas.csv';
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException("No se pudo escribir el reporte {$path}");
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['archivo', 'hoja', 'fila', 'motivo', 'nombre', 'telefono', 'seccion']);
        foreach ($rejections as $row) {
            fputcsv($handle, array_values($row));
        }
        fclose($handle);

        return $path;
    }
}
