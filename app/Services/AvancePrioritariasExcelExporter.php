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

class AvancePrioritariasExcelExporter
{
    private const BLUE = '1D3376';
    private const PINK = 'D91785';

    public function create(Collection $rows, Collection $people, array $filters): string
    {
        @set_time_limit(1200);
        $directory = storage_path('app/exports');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar la carpeta de exportaciones.');
        }

        $path = tempnam($directory, 'avance_prioritarias_');
        if ($path === false) {
            throw new RuntimeException('No fue posible crear el archivo temporal de exportación.');
        }

        $book = new Spreadsheet();
        $completed = false;

        try {
            $this->summarySheet($book, $rows, $filters);
            $this->detailSheet($book, $rows);
            $this->leadershipSheet($book, $people);
            $book->setActiveSheetIndex(0);

            $writer = new Xlsx($book);
            $writer->setPreCalculateFormulas(false);
            $writer->save($path);
            $completed = true;

            return $path;
        } finally {
            $book->disconnectWorksheets();
            unset($book);
            if (!$completed) {
                @unlink($path);
            }
        }
    }

    private function summarySheet(Spreadsheet $book, Collection $rows, array $filters): void
    {
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Resumen Ejecutivo');
        $this->title($sheet, 'AVANCE DE CONVENCIDOS Y LONAS — SECCIONES PRIORITARIAS DE MORELIA', 'A1:I1');
        $sheet->mergeCells('A2:I2')->setCellValue('A2', 'Generado: '.now()->format('d/m/Y H:i').' · '.collect($filters)->map(fn($v, $k) => "$k: $v")->implode(' · '));

        $sheet->fromArray(['Indicador', 'Secciones', 'Lista nominal 2024', 'Meta convencidos', 'Convencidos', '% avance meta', 'Meta lonas', 'Lonas', '% avance lonas'], null, 'A4');
        $sheet->fromArray([
            'Total prioritarias',
            $rows->count(),
            $rows->sum('lista_nominal_2024'),
            $rows->sum('meta_convencidos'),
            $rows->sum('convencidos'),
            null,
            $rows->sum('meta_lonas'),
            $rows->sum('lonas'),
        ], null, 'A5');
        $sheet->setCellValue('F5', '=IF(D5=0,0,E5/D5)');
        $sheet->setCellValue('I5', '=IF(G5=0,0,H5/G5)');

        $line = 7;
        foreach ([3 => 'Prioridad alta', 2 => 'Prioridad media', 1 => 'Prioridad baja'] as $priority => $label) {
            $group = $rows->where('prioridad', $priority);
            $sheet->fromArray([$label, $group->count(), $group->sum('lista_nominal_2024'), $group->sum('meta_convencidos'), $group->sum('convencidos'), null, $group->sum('meta_lonas'), $group->sum('lonas')], null, "A{$line}");
            $sheet->setCellValue("F{$line}", "=IF(D{$line}=0,0,E{$line}/D{$line})");
            $sheet->setCellValue("I{$line}", "=IF(G{$line}=0,0,H{$line}/G{$line})");
            $line++;
        }

        $line += 2;
        $sheet->fromArray(['Distrito local', 'Secciones', 'Lista nominal 2024', 'Meta convencidos', 'Convencidos', '% avance meta', 'Meta lonas', 'Lonas', '% avance lonas'], null, "A{$line}");
        $districtHeader = $line++;
        foreach ($rows->groupBy('distrito')->sortKeys() as $district => $group) {
            $sheet->fromArray(["DL ".str_pad((string) $district, 2, '0', STR_PAD_LEFT), $group->count(), $group->sum('lista_nominal_2024'), $group->sum('meta_convencidos'), $group->sum('convencidos'), null, $group->sum('meta_lonas'), $group->sum('lonas')], null, "A{$line}");
            $sheet->setCellValue("F{$line}", "=IF(D{$line}=0,0,E{$line}/D{$line})");
            $sheet->setCellValue("I{$line}", "=IF(G{$line}=0,0,H{$line}/G{$line})");
            $line++;
        }

        $this->header($sheet, 'A4:I4');
        $this->header($sheet, "A{$districtHeader}:I{$districtHeader}");
        $sheet->getStyle("C5:H{$line}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("F5:F{$line}")->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle("I5:I{$line}")->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getColumnDimension('A')->setWidth(27);
        foreach (range('B', 'I') as $column) {
            $sheet->getColumnDimension($column)->setWidth(20);
        }
        $sheet->freezePane('A4');
        $sheet->setShowGridlines(false);
    }

    private function detailSheet(Spreadsheet $book, Collection $rows): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle('Detalle Secciones');
        $this->title($sheet, 'DETALLE DE SECCIONES PRIORITARIAS', 'A1:K1');
        $sheet->fromArray(['Distrito Local', 'Sección', 'Prioridad', 'Colonias / Localidades Principales', 'Lista Nominal 2024', 'Meta Convencidos', 'Personas Convencidas', '% Avance de Meta', 'Meta Lonas', 'Lonas Colocadas', 'Estatus Operativo'], null, 'A4');

        $line = 5;
        foreach ($rows as $row) {
            $coverage = $row['lista_nominal_2024'] > 0 ? $row['convencidos'] / $row['lista_nominal_2024'] : 0;
            $status = $row['convencidos'] === 0 && $row['lonas'] === 0 ? 'Sin avance' : ($coverage >= .10 ? 'Avance alto' : ($coverage >= .03 ? 'En proceso' : 'Avance inicial'));
            $sheet->fromArray([$row['distrito'], null, $row['prioridad_etiqueta'], $row['colonias'], $row['lista_nominal_2024'], $row['meta_convencidos'], $row['convencidos'], null, $row['meta_lonas'], $row['lonas'], $status], null, "A{$line}");
            $sheet->setCellValueExplicit("B{$line}", $row['seccion'], DataType::TYPE_STRING);
            $sheet->setCellValue("H{$line}", "=IF(F{$line}=0,0,G{$line}/F{$line})");
            $line++;
        }

        $last = max(4, $line - 1);
        $this->header($sheet, 'A4:I4');
        $sheet->setAutoFilter("A4:K{$last}");
        $sheet->freezePane('A5');
        $sheet->getStyle("E5:G{$last}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("I5:J{$last}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("H5:H{$last}")->getNumberFormat()->setFormatCode('0.00%');
        foreach (['A' => 15, 'B' => 12, 'C' => 14, 'D' => 48, 'E' => 20, 'F' => 18, 'G' => 21, 'H' => 20, 'I' => 15, 'J' => 18, 'K' => 19] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getStyle("A4:K{$last}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->setShowGridlines(false);
    }

    private function leadershipSheet(Spreadsheet $book, Collection $people): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle('Liderazgos y Captura');
        $this->title($sheet, 'LIDERAZGOS Y RITMO DE CAPTURA EN SECCIONES PRIORITARIAS', 'A1:D1');
        $sheet->fromArray(['Referente', 'Personas convencidas', '% participación', 'Secciones con captura'], null, 'A4');

        $line = 5;
        $total = max(1, $people->count());
        $leaderships = $people
            ->groupBy(fn($person) => trim((string) $person->referente) ?: 'Sin referente')
            ->sortByDesc(fn(Collection $group) => $group->count());
        foreach ($leaderships as $name => $group) {
            $sheet->fromArray([$name, $group->count(), $group->count() / $total, $group->pluck('seccion')->filter()->unique()->count()], null, "A{$line}");
            $line++;
        }
        $lastLeadership = max(4, $line - 1);
        $this->header($sheet, 'A4:D4');
        $sheet->getStyle("C5:C{$lastLeadership}")->getNumberFormat()->setFormatCode('0.00%');

        $line += 2;
        $sheet->fromArray(['Mes de captura', 'Personas registradas'], null, "A{$line}");
        $this->header($sheet, "A{$line}:B{$line}");
        $line++;
        $monthly = $people->groupBy(function ($person) {
            $date = $person->fecha_convencimiento ?: $person->created_at;
            return $date ? substr((string) $date, 0, 7) : 'Sin fecha';
        })->sortKeys();
        foreach ($monthly as $month => $group) {
            $sheet->fromArray([$month, $group->count()], null, "A{$line}");
            $line++;
        }

        $sheet->getColumnDimension('A')->setWidth(35);
        foreach (['B', 'C', 'D'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(22);
        }
        $sheet->freezePane('A5');
        $sheet->setShowGridlines(false);
    }

    private function title($sheet, string $text, string $range): void
    {
        $sheet->mergeCells($range);
        $sheet->setCellValue(explode(':', $range)[0], $text);
        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => self::BLUE]],
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
    }

    private function header($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => self::BLUE]],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::PINK]]],
        ]);
    }
}
