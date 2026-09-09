<?php

namespace Tests\Unit;

use App\Services\EliasAfiliadosImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class EliasAfiliadosImportServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'elias_import_'.bin2hex(random_bytes(6));
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
        parent::tearDown();
    }

    public function test_it_reads_both_formats_and_rejects_only_rows_without_valid_geography(): void
    {
        $this->writeWorkbook('1.xlsx', [
            ['#', 'Id', 'Nombre', 'Municipio', 'Colonia', 'Calle', 'No.ext.', 'No.int.', 'CP', 'Teléfono', 'Comité', 'DV', 'Mov', 'Cuántos', 'Observaciones', 'Firma', 'Sección'],
            [1, 100, '  María   Pérez López ', 'HUETAMO', 'Centro', 'Morelos', 'S/N', null, 61940, '52 715 123 4567', null, null, 'Sí', 1, null, null, 610],
            [1, 100, '  María   Pérez López ', 'HUETAMO', 'Centro', 'Morelos', 'S/N', null, 61940, '52 715 123 4567', null, null, 'Sí', 1, null, null, 610],
            ['#', 'Id', 'Nombre', 'Municipio', 'Colonia', 'Calle', 'No.ext.', 'No.int.', 'CP', 'Teléfono', 'Comité', 'DV', 'Mov', 'Cuántos', 'Observaciones', 'Firma', 'Sección'],
            [2, 101, 'José Ruiz', 'HUETAMO', 'Centro', 'Hidalgo', 8, null, 61940, 9999999999, null, null, null, null, null, null, 610],
            [3, 102, 'Sin Sección', 'HUETAMO', 'Centro', 'Hidalgo', 9, null, 61940, 7151234568, null, null, null, null, null, null, null],
        ]);

        $this->writeWorkbook('2.xlsx', [
            ['MUNICIPIO', 'IDSECC', 'PRIORIDAD', 'APELLIDO_PATERNO', 'APELLIDO_MATERNO', 'NOMBRE', 'CALLE', 'NUM_EXT', 'NUM_INT', 'CODPOS', 'COLONIA', 'CLASIF'],
            ['HUETAMO', 611, 'A', 'CONEJO', 'MALDONADO', 'MARIA NOHEMI', '20 DE NOVIEMBRE', 7, null, 61940, 'LOS TIGRES', 'A-1'],
        ]);

        $catalog = [
            '610' => [[
                'cve_mun' => '038',
                'municipio' => 'Huetamo',
                'distrito_local' => 18,
                'distrito_federal' => 3,
            ]],
            '611' => [[
                'cve_mun' => '038',
                'municipio' => 'Huetamo',
                'distrito_local' => 18,
                'distrito_federal' => 3,
            ]],
        ];

        $result = (new EliasAfiliadosImportService())->scan($this->directory, $catalog);

        self::assertCount(3, $result['rows']);
        self::assertCount(1, $result['rejections']);
        self::assertSame(1, $result['stats']['source_duplicates']);
        self::assertSame(1, $result['stats']['repeated_headers']);
        self::assertSame(1, $result['stats']['invalid_phones']);
        self::assertSame(1, $result['stats']['missing_phones']);

        self::assertSame('MARIA PEREZ LOPEZ', $result['rows'][0]['nombre']);
        self::assertSame('7151234567', $result['rows'][0]['telefono']);
        self::assertNull($result['rows'][0]['numero_ext']);
        self::assertSame('038', $result['rows'][0]['cve_mun']);
        self::assertSame(18, $result['rows'][0]['distrito_local']);
        self::assertSame(3, $result['rows'][0]['distrito_federal']);

        self::assertNull($result['rows'][1]['telefono']);
        self::assertSame('invalid', $result['rows'][1]['_phone_state']);
        self::assertSame('MARIA NOHEMI CONEJO MALDONADO', $result['rows'][2]['nombre']);
        self::assertSame('missing', $result['rows'][2]['_phone_state']);
        self::assertSame('sección vacía o inválida', $result['rejections'][0]['reason']);
    }

    public function test_identity_without_phone_keeps_people_at_different_addresses_distinct(): void
    {
        $service = new EliasAfiliadosImportService();
        $base = [
            'nombre' => 'JUAN PEREZ',
            'telefono' => null,
            'cve_mun' => '038',
            'seccion' => '610',
            'colonia' => 'CENTRO',
            'numero_ext' => '1',
        ];

        self::assertNotSame(
            $service->identityKey($base + ['calle' => 'MORELOS']),
            $service->identityKey($base + ['calle' => 'HIDALGO'])
        );
    }

    private function writeWorkbook(string $name, array $rows): void
    {
        $workbook = new Spreadsheet();
        $workbook->getActiveSheet()->fromArray($rows, null, 'A1');
        (new Xlsx($workbook))->save($this->directory.DIRECTORY_SEPARATOR.$name);
        $workbook->disconnectWorksheets();
    }
}
