<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

class LonasExcelExporter
{
    private const HEADER_ROW = 4;
    private const FIRST_DATA_ROW = 5;
    private const LAST_COLUMN = 'P';
    private const QUERY_CHUNK_SIZE = 100;
    private const PHOTO_WIDTH = 145;
    private const PHOTO_HEIGHT = 100;
    private const PHOTO_QUALITY = 65;

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

        $assetDirectory = $exportDirectory
            .DIRECTORY_SEPARATOR
            .uniqid('lonas_assets_', true);

        $cacheDirectory = $exportDirectory
            .DIRECTORY_SEPARATOR
            .uniqid('lonas_cache_', true);

        if (
            !mkdir($assetDirectory, 0775, true)
            && !is_dir($assetDirectory)
        ) {
            @unlink($path);

            throw new RuntimeException(
                'No fue posible preparar las fotografías para la exportación.'
            );
        }

        if (
            !mkdir($cacheDirectory, 0775, true)
            && !is_dir($cacheDirectory)
        ) {
            $this->removeDirectory($assetDirectory);
            @unlink($path);

            throw new RuntimeException(
                'No fue posible preparar el caché de la exportación.'
            );
        }

        $previousCache = Settings::getCache();

        $filesystemCache = new FilesystemAdapter(
            'lonas_excel',
            0,
            $cacheDirectory
        );

        $cellCache = new Psr16Cache($filesystemCache);

        Settings::setCache($cellCache);

        $spreadsheet = null;
        $completed = false;

        try {
            $total = (clone $query)->count();

            $spreadsheet = new Spreadsheet();

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Lonas');
            $sheet->setShowGridlines(false);

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

            foreach ($headers as $index => $header) {
                $sheet->setCellValueByColumnAndRow(
                    $index + 1,
                    self::HEADER_ROW,
                    $header
                );
            }

            $row = self::FIRST_DATA_ROW;

            (clone $query)
                ->reorder('id')
                ->chunkById(
                    self::QUERY_CHUNK_SIZE,
                    function ($lonas) use (
                        $sheet,
                        $assetDirectory,
                        &$row
                    ) {
                        foreach ($lonas as $lona) {
                            $this->writeRow(
                                $sheet,
                                $lona,
                                $row,
                                $assetDirectory
                            );

                            $row++;
                        }

                        unset($lonas);

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

            $writer->setUseDiskCaching(
                true,
                $exportDirectory
            );

            $writer->save($path);

            $completed = true;

            return $path;
        } finally {
            if ($spreadsheet) {
                $spreadsheet->disconnectWorksheets();
            }

            unset($spreadsheet);

            $filesystemCache->clear();

            Settings::setCache($previousCache);

            $this->removeDirectory(
                $assetDirectory
            );

            $this->removeDirectory(
                $cacheDirectory
            );

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
        int $row,
        string $assetDirectory
    ): void {
        $sheet->setCellValue(
            "A{$row}",
            (int) $lona->id
        );

        $sheet->setCellValueExplicit(
            "C{$row}",
            (string) $lona->seccion,
            DataType::TYPE_STRING
        );

        $sheet->setCellValue(
            "D{$row}",
            $lona->direccion
        );

        $sheet->setCellValue(
            "E{$row}",
            $lona->responsable
        );

        $sheet->setCellValue(
            "F{$row}",
            $lona->capturado_por === null
                ? null
                : (int) $lona->capturado_por
        );

        $sheet->setCellValue(
            "G{$row}",
            optional($lona->capturista)->name ?: '—'
        );

        $sheet->setCellValue(
            "H{$row}",
            (float) $lona->lat
        );

        $sheet->setCellValue(
            "I{$row}",
            (float) $lona->lng
        );

        $sheet->setCellValue(
            "J{$row}",
            $lona->ubicacion_google
        );

        $sheet->setCellValue(
            "K{$row}",
            $lona->foto_nombre_original
        );

        $sheet->setCellValue(
            "L{$row}",
            $lona->foto_path
        );

        $sheet->setCellValue(
            "M{$row}",
            $lona->foto_bytes_original === null
                ? null
                : (int) $lona->foto_bytes_original
        );

        $sheet->setCellValue(
            "N{$row}",
            $lona->foto_bytes_final === null
                ? null
                : (int) $lona->foto_bytes_final
        );

        if ($lona->created_at) {
            $sheet->setCellValue(
                "O{$row}",
                Date::PHPToExcel($lona->created_at)
            );
        }

        if ($lona->updated_at) {
            $sheet->setCellValue(
                "P{$row}",
                Date::PHPToExcel($lona->updated_at)
            );
        }

        if ($lona->ubicacion_google) {
            $sheet
                ->getCell("J{$row}")
                ->getHyperlink()
                ->setUrl($lona->ubicacion_google);
        }

        $this->addPhoto(
            $sheet,
            $lona->foto_path,
            $row,
            $assetDirectory
        );

        $sheet
            ->getRowDimension($row)
            ->setRowHeight(82);
    }

    private function addPhoto(
        $sheet,
        ?string $storagePath,
        int $row,
        string $assetDirectory
    ): void {
        $thumbnailPath = $this->createThumbnail(
            $storagePath,
            $row,
            $assetDirectory
        );

        if (!$thumbnailPath) {
            $sheet->setCellValue(
                "B{$row}",
                'No disponible'
            );

            return;
        }

        $drawing = new Drawing();

        $drawing->setName(
            'Lona '.$row
        );

        $drawing->setDescription(
            'Fotografía de la lona'
        );

        $drawing->setPath(
            $thumbnailPath
        );

        $drawing->setResizeProportional(true);

        $drawing->setWidthAndHeight(
            self::PHOTO_WIDTH,
            self::PHOTO_HEIGHT
        );

        $drawing->setCoordinates(
            "B{$row}"
        );

        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);

        $drawing->setWorksheet(
            $sheet
        );
    }

    private function createThumbnail(
        ?string $storagePath,
        int $row,
        string $assetDirectory
    ): ?string {
        if (
            !$storagePath
            || !Storage::disk('local')->exists($storagePath)
        ) {
            return null;
        }

        $absolutePath = Storage::disk('local')
            ->path($storagePath);

        $source = null;

        if (function_exists('imagecreatefromjpeg')) {
            $source = @imagecreatefromjpeg(
                $absolutePath
            );
        }

        if (
            !$source
            && function_exists('imagecreatefromstring')
        ) {
            $binary = @file_get_contents(
                $absolutePath
            );

            if ($binary !== false) {
                $source = @imagecreatefromstring(
                    $binary
                );

                unset($binary);
            }
        }

        if (!$source) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if (
            $sourceWidth <= 0
            || $sourceHeight <= 0
        ) {
            imagedestroy($source);

            return null;
        }

        $scale = min(
            self::PHOTO_WIDTH / $sourceWidth,
            self::PHOTO_HEIGHT / $sourceHeight,
            1
        );

        $targetWidth = max(
            1,
            (int) round(
                $sourceWidth * $scale
            )
        );

        $targetHeight = max(
            1,
            (int) round(
                $sourceHeight * $scale
            )
        );

        $thumbnail = imagecreatetruecolor(
            $targetWidth,
            $targetHeight
        );

        if (!$thumbnail) {
            imagedestroy($source);

            return null;
        }

        $white = imagecolorallocate(
            $thumbnail,
            255,
            255,
            255
        );

        imagefill(
            $thumbnail,
            0,
            0,
            $white
        );

        imagecopyresampled(
            $thumbnail,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $thumbnailPath = $assetDirectory
            .DIRECTORY_SEPARATOR
            .$row
            .'.jpg';

        $written = @imagejpeg(
            $thumbnail,
            $thumbnailPath,
            self::PHOTO_QUALITY
        );

        imagedestroy($thumbnail);
        imagedestroy($source);

        return $written
            ? $thumbnailPath
            : null;
    }

    private function removeDirectory(
        string $directory
    ): void {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if (
                $item === '.'
                || $item === '..'
            ) {
                continue;
            }

            $path = $directory
                .DIRECTORY_SEPARATOR
                .$item;

            if (is_dir($path)) {
                $this->removeDirectory(
                    $path
                );
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
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
                < 512 * 1024 * 1024
        ) {
            @ini_set(
                'memory_limit',
                '512M'
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
            $dataRange = 'A'
                .self::FIRST_DATA_ROW
                .':'
                .self::LAST_COLUMN
                .$lastRow;

            $sheet
                ->getStyle($dataRange)
                ->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_HAIR,
                            'color' => [
                                'rgb' => 'D9D9D9',
                            ],
                        ],
                    ],
                ]);

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

            $sheet
                ->getStyle(
                    'J'
                    .self::FIRST_DATA_ROW
                    .':J'
                    .$lastRow
                )
                ->getFont()
                ->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color(
                        '0563C1'
                    )
                )
                ->setUnderline(true);
        }

        $widths = [
            'A' => 8,
            'B' => 23,
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

        if ($lastRow >= self::FIRST_DATA_ROW) {
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
    }
}
