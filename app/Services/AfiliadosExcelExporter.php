<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class AfiliadosExcelExporter
{
    private const HEADER_ROW = 4;
    private const FIRST_DATA_ROW = 5;
    private const LAST_COLUMN = 'U';
    private const QUERY_CHUNK_SIZE = 500;

    public function create(Builder $query): string
    {
        @set_time_limit(1200);

        $memoryLimit = trim((string) ini_get('memory_limit'));

        if ($memoryLimit !== '-1') {
            @ini_set('memory_limit', '768M');
        }

        $exportDirectory = storage_path('app/exports');

        if (
            !is_dir($exportDirectory)
            && !mkdir($exportDirectory, 0775, true)
            && !is_dir($exportDirectory)
        ) {
            throw new RuntimeException(
                'No fue posible preparar la carpeta de exportaciones.'
            );
        }

        $path = tempnam($exportDirectory, 'afiliados_');

        if ($path === false) {
            throw new RuntimeException(
                'No fue posible crear el archivo temporal de exportación.'
            );
        }

        $spreadsheet = null;
        $completed = false;

        try {
            $total = (clone $query)->count();

            $spreadsheet = new Spreadsheet();

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Afiliados');
            $sheet->setShowGridlines(false);
            $sheet->getDefaultRowDimension()->setRowHeight(22);

            $sheet->mergeCells('A1:'.self::LAST_COLUMN.'1');
            $sheet->setCellValue('A1', 'Listado de personas convencidas');

            $sheet->mergeCells('A2:'.self::LAST_COLUMN.'2');
            $sheet->setCellValue(
                'A2',
                'Generado el '
                .now('America/Mexico_City')->format('d/m/Y H:i')
                .' · '
                .$total
                .' registro(s)'
            );

            $headers = [
                'ID',
                'Nombre completo',
                'Edad',
                'Sexo',
                'Teléfono',
                'Correo electrónico',
                'Clave de elector',
                'Municipio',
                'Clave municipio',
                'Sección',
                'Distrito local',
                'Distrito federal',
                'Localidad',
                'Colonia',
                'Perfil',
                'Tipo de vínculo',
                'Número MOV',
                'Estatus',
                'Fecha convencimiento',
                'ID capturista',
                'Capturista',
            ];

            $sheet->fromArray(
                $headers,
                null,
                'A'.self::HEADER_ROW
            );

            $row = self::FIRST_DATA_ROW;

            (clone $query)
                ->reorder('afiliados.id')
                ->chunkById(
                    self::QUERY_CHUNK_SIZE,
                    function ($afiliados) use ($sheet, &$row) {
                        foreach ($afiliados as $afiliado) {
                            $nombre = $afiliado->nombre_completo
                                ?? trim(
                                    ($afiliado->nombre ?? '')
                                    .' '
                                    .($afiliado->apellido_paterno ?? '')
                                    .' '
                                    .($afiliado->apellido_materno ?? '')
                                );

                            $fecha = $afiliado->fecha_convencimiento
                                ? Date::PHPToExcel(
                                    new \DateTime($afiliado->fecha_convencimiento)
                                )
                                : null;

                            $sheet->fromArray([
                                (int) $afiliado->id,
                                $nombre,
                                $afiliado->edad,
                                $afiliado->sexo,
                                $afiliado->telefono,
                                $afiliado->email,
                                $afiliado->clave_elector,
                                $afiliado->municipio,
                                $afiliado->cve_mun,
                                $afiliado->seccion,
                                $afiliado->distrito_local,
                                $afiliado->distrito_federal,
                                $afiliado->localidad,
                                $afiliado->colonia,
                                $afiliado->perfil,
                                $afiliado->tipo_vinculo,
                                $afiliado->numero_mov,
                                $afiliado->estatus,
                                $fecha,
                                $afiliado->capturista_id,
                                $afiliado->capturista_nombre ?? '—',
                            ], null, "A{$row}");

                            $sheet->setCellValueExplicit(
                                "G{$row}",
                                (string) $afiliado->clave_elector,
                                DataType::TYPE_STRING
                            );

                            $sheet->setCellValueExplicit(
                                "I{$row}",
                                (string) $afiliado->cve_mun,
                                DataType::TYPE_STRING
                            );

                            $sheet->setCellValueExplicit(
                                "J{$row}",
                                (string) $afiliado->seccion,
                                DataType::TYPE_STRING
                            );

                            $row++;
                        }

                        if (function_exists('gc_collect_cycles')) {
                            gc_collect_cycles();
                        }
                    },
                    'afiliados.id',
                    'id'
                );

            $lastRow = max(self::HEADER_ROW, $row - 1);

            $sheet->setAutoFilter(
                'A'.self::HEADER_ROW.':'.self::LAST_COLUMN.$lastRow
            );

            $sheet->freezePane(
                'A'.self::FIRST_DATA_ROW
            );

            $sheet
                ->getStyle('A1:'.self::LAST_COLUMN.'1')
                ->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => '7A0019'],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 16,
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

            $sheet
                ->getStyle('A2:'.self::LAST_COLUMN.'2')
                ->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => 'F4E6E9'],
                    ],
                    'font' => [
                        'color' => ['rgb' => '5C0013'],
                        'italic' => true,
                    ],
                ]);

            $headerRange =
                'A'.self::HEADER_ROW
                .':'.self::LAST_COLUMN.self::HEADER_ROW;

            $sheet
                ->getStyle($headerRange)
                ->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => '5C0013'],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '7A0019'],
                        ],
                    ],
                ]);

            if ($lastRow >= self::FIRST_DATA_ROW) {
                $sheet
                    ->getStyle(
                        'A'.self::FIRST_DATA_ROW
                        .':'.self::LAST_COLUMN.$lastRow
                    )
                    ->getAlignment()
                    ->setVertical(
                        Alignment::VERTICAL_CENTER
                    );

                $sheet
                    ->getStyle(
                        'B'.self::FIRST_DATA_ROW
                        .':U'.$lastRow
                    )
                    ->getAlignment()
                    ->setWrapText(true);

                $sheet
                    ->getStyle(
                        'S'.self::FIRST_DATA_ROW
                        .':S'.$lastRow
                    )
                    ->getNumberFormat()
                    ->setFormatCode('dd/mm/yyyy hh:mm');
            }

            $widths = [
                'A' => 8,
                'B' => 35,
                'C' => 9,
                'D' => 10,
                'E' => 18,
                'F' => 30,
                'G' => 25,
                'H' => 24,
                'I' => 14,
                'J' => 12,
                'K' => 16,
                'L' => 18,
                'M' => 24,
                'N' => 24,
                'O' => 24,
                'P' => 18,
                'Q' => 16,
                'R' => 16,
                'S' => 22,
                'T' => 15,
                'U' => 28,
            ];

            foreach ($widths as $column => $width) {
                $sheet
                    ->getColumnDimension($column)
                    ->setWidth($width);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save($path);

            $completed = true;

            return $path;
        } finally {
            if ($spreadsheet) {
                $spreadsheet->disconnectWorksheets();
            }

            unset($spreadsheet);

            if (!$completed) {
                @unlink($path);
            }

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
    }
}
