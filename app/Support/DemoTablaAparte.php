<?php

namespace App\Support;

use Generator;
use RuntimeException;

final class DemoTablaAparte
{
    public const MARKER = 'DEMO_TABLAAPARTE_2026-09-02';
    public const REFERENTE = 'Gladyz Butanda';
    public const EXPECTED_ROWS = 110000;
    public const EXPECTED_AFFILIATES = 99000;
    public const EXPECTED_NON_AFFILIATES = 11000;
    public const SHA256 = '4f00c4b3fcf5a83d8ebe481b5421035e8f1fc5608685000d9e13f0afd1e92775';

    private const HEADERS = [
        'source_row',
        'cve_mun',
        'municipio',
        'seccion',
        'distrito_federal',
        'distrito_local',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'calle',
        'numero_ext',
        'numero_int',
        'colonia',
        'cp',
    ];

    public static function path(): string
    {
        return database_path('data/demo_tablaaparte.csv.gz');
    }

    public static function validateFile(): void
    {
        $path = self::path();

        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("No se puede leer el lote de demostración: {$path}");
        }

        if (hash_file('sha256', $path) !== self::SHA256) {
            throw new RuntimeException('El lote de demostración fue modificado o está incompleto.');
        }
    }

    public static function rows(): Generator
    {
        self::validateFile();

        $handle = gzopen(self::path(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el lote de demostración.');
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers !== self::HEADERS) {
                throw new RuntimeException('Los encabezados del lote de demostración no son válidos.');
            }

            while (($values = fgetcsv($handle)) !== false) {
                if (count($values) !== count(self::HEADERS)) {
                    throw new RuntimeException('El lote de demostración contiene una fila inválida.');
                }

                yield array_combine(self::HEADERS, $values);
            }
        } finally {
            gzclose($handle);
        }
    }

    public static function statusForSourceRow(string $sourceRow): string
    {
        return (int) $sourceRow % 10 === 0
            ? 'descartado'
            : 'validado';
    }
}
