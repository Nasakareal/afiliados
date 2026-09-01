<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class AvanceExcelExporter
{
    private const HEADER_ROW = 5;
    private const FIRST_DATA_ROW = 6;

    public function create(
        Collection $avance,
        array $totales,
        Builder $personasQuery,
        array $filtros
    ): string {
        @set_time_limit(1200);

        $directory = storage_path('app/exports');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar la carpeta de exportaciones.');
        }

        $path = tempnam($directory, 'avance_');

        if ($path === false) {
            throw new RuntimeException('No fue posible crear el archivo temporal de exportación.');
        }

        $spreadsheet = new Spreadsheet();
        $completed = false;

        try {
            $this->writeProgressSheet(
                $spreadsheet->getActiveSheet(),
                $avance,
                $totales,
                $filtros
            );

            $this->writePeopleSheet(
                $spreadsheet->createSheet(),
                $personasQuery,
                $filtros
            );

            $spreadsheet->setActiveSheetIndex(0);
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

    private function writeProgressSheet(
        Worksheet $sheet,
        Collection $avance,
        array $totales,
        array $filtros
    ): void {
        $sheet->setTitle('Avance');
        $sheet->setShowGridlines(false);
        $this->writeTitleAndFilters($sheet, 'Avance distrital filtrado', 'I', $filtros);

        $headers = [
            'DL / DFn',
            'Municipio',
            'Secciones',
            'Meta convencidos',
            'Total convencidos',
            '% convencidos',
            'Meta lonas',
            'Total lonas',
            '% lonas',
        ];
        $sheet->fromArray($headers, null, 'A'.self::HEADER_ROW);

        $row = self::FIRST_DATA_ROW;

        foreach ($avance as $fila) {
            $sheet->fromArray([
                str_pad((string) $fila['distrito_local'], 2, '0', STR_PAD_LEFT)
                    .' / '.($fila['distritos_federales'] ?: '—'),
                $fila['municipio'],
                (int) $fila['secciones'],
                (int) $fila['meta_convencidos'],
                (int) $fila['total_convencidos'],
                $fila['meta_convencidos'] > 0
                    ? (float) $fila['porcentaje_convencidos'] / 100
                    : null,
                (int) $fila['meta_lonas'],
                (int) $fila['total_lonas'],
                $fila['meta_lonas'] > 0
                    ? (float) $fila['porcentaje_lonas'] / 100
                    : null,
            ], null, "A{$row}");
            $row++;
        }

        $totalRow = $row;
        $sheet->fromArray([
            'TOTAL',
            null,
            (int) $totales['secciones'],
            (int) $totales['meta_convencidos'],
            (int) $totales['total_convencidos'],
            $totales['meta_convencidos'] > 0
                ? (float) $totales['porcentaje_convencidos'] / 100
                : null,
            (int) $totales['meta_lonas'],
            (int) $totales['total_lonas'],
            $totales['meta_lonas'] > 0
                ? (float) $totales['porcentaje_lonas'] / 100
                : null,
        ], null, "A{$totalRow}");
        $sheet->mergeCells("A{$totalRow}:B{$totalRow}");

        $lastRow = max(self::HEADER_ROW, $totalRow);
        $sheet->setAutoFilter('A'.self::HEADER_ROW.":I{$lastRow}");
        $sheet->freezePane('A'.self::FIRST_DATA_ROW);
        $sheet->getStyle('F'.self::FIRST_DATA_ROW.":F{$lastRow}")
            ->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('I'.self::FIRST_DATA_ROW.":I{$lastRow}")
            ->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '7A0019']],
        ]);

        $this->styleTable($sheet, 'I', $lastRow);
        $this->setWidths($sheet, [
            'A' => 14,
            'B' => 28,
            'C' => 12,
            'D' => 18,
            'E' => 18,
            'F' => 16,
            'G' => 14,
            'H' => 14,
            'I' => 12,
        ]);
    }

    private function writePeopleSheet(
        Worksheet $sheet,
        Builder $query,
        array $filtros
    ): void {
        $sheet->setTitle('Personas convencidas');
        $sheet->setShowGridlines(false);
        $this->writeTitleAndFilters($sheet, 'Personas convencidas y sus distritos', 'M', $filtros);

        $headers = [
            'ID',
            'Nombre completo',
            'Teléfono',
            'Referente',
            'Capturista',
            'Sección',
            'Municipio',
            'Clave municipio',
            'Distrito local',
            'Distrito federal',
            'Fecha convencimiento',
            'Fecha captura',
            'Alcance electoral',
        ];
        $sheet->fromArray($headers, null, 'A'.self::HEADER_ROW);

        $row = self::FIRST_DATA_ROW;

        (clone $query)
            ->orderBy('a.id')
            ->chunkById(500, function ($personas) use ($sheet, &$row) {
                foreach ($personas as $persona) {
                    $nombre = trim(implode(' ', array_filter([
                        $persona->nombre,
                        $persona->apellido_paterno,
                        $persona->apellido_materno,
                    ])));
                    $fechaConvencimiento = $persona->fecha_convencimiento
                        ? Date::PHPToExcel(Carbon::parse($persona->fecha_convencimiento))
                        : null;
                    $fechaCaptura = $persona->created_at
                        ? Date::PHPToExcel(Carbon::parse($persona->created_at))
                        : null;
                    $dl = str_pad((string) $persona->distrito_local, 2, '0', STR_PAD_LEFT);
                    $df = str_pad((string) $persona->distrito_federal, 2, '0', STR_PAD_LEFT);

                    $sheet->fromArray([
                        (int) $persona->id,
                        $nombre,
                        $persona->telefono,
                        trim((string) $persona->referente),
                        $persona->capturista,
                        (string) $persona->seccion,
                        $persona->municipio,
                        (string) $persona->cve_mun,
                        (int) $persona->distrito_local,
                        (int) $persona->distrito_federal,
                        $fechaConvencimiento,
                        $fechaCaptura,
                        "DL {$dl} / DFn {$df}",
                    ], null, "A{$row}");

                    $sheet->setCellValueExplicit("C{$row}", (string) $persona->telefono, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("F{$row}", (string) $persona->seccion, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("H{$row}", (string) $persona->cve_mun, DataType::TYPE_STRING);
                    $row++;
                }
            }, 'a.id', 'id');

        $lastRow = max(self::HEADER_ROW, $row - 1);
        $sheet->setAutoFilter('A'.self::HEADER_ROW.":M{$lastRow}");
        $sheet->freezePane('A'.self::FIRST_DATA_ROW);

        if ($lastRow >= self::FIRST_DATA_ROW) {
            $sheet->getStyle('K'.self::FIRST_DATA_ROW.":L{$lastRow}")
                ->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
        }

        $this->styleTable($sheet, 'M', $lastRow);
        $this->setWidths($sheet, [
            'A' => 9,
            'B' => 34,
            'C' => 18,
            'D' => 25,
            'E' => 25,
            'F' => 11,
            'G' => 28,
            'H' => 15,
            'I' => 14,
            'J' => 16,
            'K' => 21,
            'L' => 21,
            'M' => 20,
        ]);
    }

    private function writeTitleAndFilters(
        Worksheet $sheet,
        string $title,
        string $lastColumn,
        array $filtros
    ): void {
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue(
            'A2',
            'Filtros: '.collect($filtros)
                ->map(fn($value, $label) => "{$label}: {$value}")
                ->implode(' · ')
        );
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->setCellValue('A3', 'Generado el '.now()->format('d/m/Y H:i'));

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1D3376']],
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle("A2:{$lastColumn}3")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'EEF1EB']],
            'font' => ['color' => ['rgb' => '303849']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
    }

    private function styleTable(
        Worksheet $sheet,
        string $lastColumn,
        int $lastRow
    ): void {
        $sheet->getStyle('A'.self::HEADER_ROW.":{$lastColumn}".self::HEADER_ROW)
            ->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1D3376']],
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => 'D91785'],
                    ],
                ],
            ]);
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(34);

        if ($lastRow >= self::FIRST_DATA_ROW) {
            $sheet->getStyle('A'.self::FIRST_DATA_ROW.":{$lastColumn}{$lastRow}")
                ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
    }

    private function setWidths(Worksheet $sheet, array $widths): void
    {
        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }
}
