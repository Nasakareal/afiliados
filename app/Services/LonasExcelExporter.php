<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class LonasExcelExporter
{
    private const HEADER_ROW = 4;
    private const FIRST_DATA_ROW = 5;
    private const LAST_COLUMN = 'P';
    private const QUERY_CHUNK_SIZE = 500;

    public function create(Builder $query): string
    {
        $this->ensureExecutionCapacity();

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

        $path = tempnam($exportDirectory, 'lonas_');

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
            $sheet->setTitle('Lonas');
            $sheet->setShowGridlines(false);
            $sheet->getDefaultRowDimension()->setRowHeight(22);

            $sheet->mergeCells(
                'A1:'.self::LAST_COLUMN.'1'
            );

            $sheet->setCellValue(
                'A1',
                'Listado de lonas'
            );

            $sheet->mergeCells(
                'A2:'.self::LAST_COLUMN.'2'
            );

            $sheet->setCellValue(
                'A2',
                'Generado el '
                .now()->format('d/m/Y H:i')
                .' · '
                .$total
                .' registro(s)'
            );

            $headers = [
                'ID',
                'Fotografía',
                'Sección',
                'Dirección',
                'Responsable',
                'ID capturista',
                'Capturista',
                'Latitud',
                'Longitud',
                'Ubicación Google Maps',
                'Archivo original',
                'Ruta de fotografía',
                'Bytes originales',
                'Bytes procesados',
                'Registrada',
                'Actualizada',
            ];

            $sheet->fromArray(
                $headers,
                null,
                'A'.self::HEADER_ROW
            );

            $row = self::FIRST_DATA_ROW;

            (clone $query)
                ->reorder('id')
                ->chunkById(
                    self::QUERY_CHUNK_SIZE,
                    function ($lonas) use ($sheet, &$row) {
                        foreach ($lonas as $lona) {
                            $this->writeRow(
                                $sheet,
                                $lona,
                                $row
                            );

                            $row++;
                        }

                        if (function_exists('gc_collect_cycles')) {
                            gc_collect_cycles();
                        }
                    }
                );

            $lastRow = max(
                self::HEADER_ROW,
                $row - 1
            );

            $sheet->setAutoFilter(
                'A'
                .self::HEADER_ROW
                .':'
                .self::LAST_COLUMN
                .$lastRow
            );

            $sheet->freezePane(
                'A'.self::FIRST_DATA_ROW
            );

            $this->applyStyles(
                $spreadsheet,
                $lastRow
            );

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

    private function writeRow(
        $sheet,
        $lona,
        int $row
    ): void {
        $createdAt = $lona->created_at
            ? Date::PHPToExcel($lona->created_at)
            : null;

        $updatedAt = $lona->updated_at
            ? Date::PHPToExcel($lona->updated_at)
            : null;

        $photoText = $lona->foto_path
            ? 'Ver fotografía'
            : 'No disponible';

        $sheet->fromArray(
            [
                (int) $lona->id,
                $photoText,
                (string) $lona->seccion,
                $lona->direccion,
                $lona->responsable,
                $lona->capturado_por === null
                    ? null
                    : (int) $lona->capturado_por,
                optional($lona->capturista)->name ?: '—',
                $lona->lat === null
                    ? null
                    : (float) $lona->lat,
                $lona->lng === null
                    ? null
                    : (float) $lona->lng,
                $lona->ubicacion_google,
                $lona->foto_nombre_original,
                $lona->foto_path,
                $lona->foto_bytes_original === null
                    ? null
                    : (int) $lona->foto_bytes_original,
                $lona->foto_bytes_final === null
                    ? null
                    : (int) $lona->foto_bytes_final,
                $createdAt,
                $updatedAt,
            ],
            null,
            "A{$row}"
        );

        $sheet->setCellValueExplicit(
            "C{$row}",
            (string) $lona->seccion,
            DataType::TYPE_STRING
        );

        if ($lona->foto_path) {
            $sheet
                ->getCell("B{$row}")
                ->getHyperlink()
                ->setUrl(
                    route('lonas.foto', $lona)
                );
        }

        if ($lona->ubicacion_google) {
            $sheet
                ->getCell("J{$row}")
                ->getHyperlink()
                ->setUrl(
                    $lona->ubicacion_google
                );
        }
    }

    private function ensureExecutionCapacity(): void
    {
        @set_time_limit(1200);

        $memoryLimit = trim(
            (string) ini_get('memory_limit')
        );

        if (
            $memoryLimit !== '-1'
            && $this->memoryLimitInBytes($memoryLimit)
                < 768 * 1024 * 1024
        ) {
            @ini_set(
                'memory_limit',
                '768M'
            );
        }
    }

    private function memoryLimitInBytes(
        string $value
    ): int {
        $number = (int) $value;

        $unit = strtolower(
            substr($value, -1)
        );

        if ($unit === 'g') {
            return $number
                * 1024
                * 1024
                * 1024;
        }

        if ($unit === 'm') {
            return $number
                * 1024
                * 1024;
        }

        if ($unit === 'k') {
            return $number
                * 1024;
        }

        return $number;
    }

    private function applyStyles(
        Spreadsheet $spreadsheet,
        int $lastRow
    ): void {
        $sheet = $spreadsheet->getActiveSheet();

        $sheet
            ->getStyle(
                'A1:'.self::LAST_COLUMN.'1'
            )
            ->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => [
                        'rgb' => '7A0019',
                    ],
                ],
                'font' => [
                    'bold' => true,
                    'color' => [
                        'rgb' => 'FFFFFF',
                    ],
                    'size' => 16,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

        $sheet
            ->getRowDimension(1)
            ->setRowHeight(30);

        $sheet
            ->getStyle(
                'A2:'.self::LAST_COLUMN.'2'
            )
            ->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => [
                        'rgb' => 'F4E6E9',
                    ],
                ],
                'font' => [
                    'color' => [
                        'rgb' => '5C0013',
                    ],
                    'italic' => true,
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

        $sheet
            ->getRowDimension(2)
            ->setRowHeight(22);

        $headerRange = 'A'
            .self::HEADER_ROW
            .':'
            .self::LAST_COLUMN
            .self::HEADER_ROW;

        $sheet
            ->getStyle($headerRange)
            ->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => [
                        'rgb' => '5C0013',
                    ],
                ],
                'font' => [
                    'bold' => true,
                    'color' => [
                        'rgb' => 'FFFFFF',
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => [
                            'rgb' => '7A0019',
                        ],
                    ],
                ],
            ]);

        $sheet
            ->getRowDimension(
                self::HEADER_ROW
            )
            ->setRowHeight(32);

        if ($lastRow >= self::FIRST_DATA_ROW) {
            $sheet
                ->getStyle(
                    'A'
                    .self::FIRST_DATA_ROW
                    .':'
                    .self::LAST_COLUMN
                    .$lastRow
                )
                ->getAlignment()
                ->setVertical(
                    Alignment::VERTICAL_CENTER
                );

            $sheet
                ->getStyle(
                    'D'
                    .self::FIRST_DATA_ROW
                    .':G'
                    .$lastRow
                )
                ->getAlignment()
                ->setWrapText(true);

            $sheet
                ->getStyle(
                    'J'
                    .self::FIRST_DATA_ROW
                    .':L'
                    .$lastRow
                )
                ->getAlignment()
                ->setWrapText(true);

            $sheet
                ->getStyle(
                    'H'
                    .self::FIRST_DATA_ROW
                    .':I'
                    .$lastRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '0.0000000'
                );

            $sheet
                ->getStyle(
                    'M'
                    .self::FIRST_DATA_ROW
                    .':N'
                    .$lastRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '#,##0'
                );

            $sheet
                ->getStyle(
                    'O'
                    .self::FIRST_DATA_ROW
                    .':P'
                    .$lastRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    'dd/mm/yyyy hh:mm'
                );

            $hyperlinkColor = new Color(
                '0563C1'
            );

            $sheet
                ->getStyle(
                    'B'
                    .self::FIRST_DATA_ROW
                    .':B'
                    .$lastRow
                )
                ->getFont()
                ->setColor(
                    clone $hyperlinkColor
                )
                ->setUnderline(true);

            $sheet
                ->getStyle(
                    'J'
                    .self::FIRST_DATA_ROW
                    .':J'
                    .$lastRow
                )
                ->getFont()
                ->setColor(
                    clone $hyperlinkColor
                )
                ->setUnderline(true);

            $sheet
                ->getStyle(
                    'A'
                    .self::FIRST_DATA_ROW
                    .':C'
                    .$lastRow
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            $sheet
                ->getStyle(
                    'F'
                    .self::FIRST_DATA_ROW
                    .':F'
                    .$lastRow
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );
        }

        $widths = [
            'A' => 8,
            'B' => 20,
            'C' => 12,
            'D' => 42,
            'E' => 24,
            'F' => 14,
            'G' => 24,
            'H' => 15,
            'I' => 15,
            'J' => 42,
            'K' => 28,
            'L' => 38,
            'M' => 18,
            'N' => 18,
            'O' => 20,
            'P' => 20,
        ];

        foreach (
            $widths as $column => $width
        ) {
            $sheet
                ->getColumnDimension($column)
                ->setWidth($width);
        }
    }
}
