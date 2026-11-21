<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class AvanceSeccionesExcelExporter
{
    private const HEADER_ROW = 4;
    private const FIRST_DATA_ROW = 5;

    public function create(Collection $rows, array $filters): string
    {
        @set_time_limit(1200);

        $directory = storage_path('app/exports');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar la carpeta de exportaciones.');
        }

        $path = tempnam($directory, 'avance_secciones_');
        if ($path === false) {
            throw new RuntimeException('No fue posible crear el archivo temporal de exportación.');
        }

        $spreadsheet = new Spreadsheet();
        $completed = false;

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Avance por sección');
            $sheet->setShowGridlines(false);
            $sheet->mergeCells('A1:E1');
            $sheet->setCellValue('A1', 'Lonas y convencidos por sección');
            $sheet->mergeCells('A2:E2');
            $sheet->setCellValue(
                'A2',
                'Filtros: '.collect($filters)
                    ->map(fn($value, $label) => "{$label}: {$value}")
                    ->implode(' · ')
            );
            $sheet->fromArray(
                ['DISTRITO', 'SECCIÓN', 'COLONIAS', '# LONAS', '# CONVENCIDOS'],
                null,
                'A'.self::HEADER_ROW
            );

            $rowNumber = self::FIRST_DATA_ROW;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    $row['distrito'],
                    $row['seccion'],
                    $row['colonias'],
                    $row['lonas'],
                    $row['convencidos'],
                ], null, "A{$rowNumber}");
                $sheet->setCellValueExplicit(
                    "B{$rowNumber}",
                    $row['seccion'],
                    DataType::TYPE_STRING
                );
                $rowNumber++;
            }

            $lastRow = max(self::HEADER_ROW, $rowNumber - 1);
            $sheet->setAutoFilter('A'.self::HEADER_ROW.":E{$lastRow}");
            $sheet->freezePane('A'.self::FIRST_DATA_ROW);

            $sheet->getStyle('A1:E1')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1D3376']],
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(30);
            $sheet->getStyle('A2:E2')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'EEF1EB']],
                'font' => ['italic' => true, 'color' => ['rgb' => '303849']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getStyle('A'.self::HEADER_ROW.':E'.self::HEADER_ROW)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1D3376']],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => 'D91785'],
                    ],
                ],
            ]);
            $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(28);

            if ($lastRow >= self::FIRST_DATA_ROW) {
                $sheet->getStyle('A'.self::FIRST_DATA_ROW.":E{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A'.self::FIRST_DATA_ROW.":B{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D'.self::FIRST_DATA_ROW.":E{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }

            foreach ([
                'A' => 13,
                'B' => 13,
                'C' => 60,
                'D' => 13,
                'E' => 17,
            ] as $column => $width) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save($path);
            $completed = true;

            return $path;
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if (!$completed) {
                @unlink($path);
            }
        }
    }
}
