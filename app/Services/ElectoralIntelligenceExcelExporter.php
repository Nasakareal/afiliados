<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ElectoralIntelligenceExcelExporter
{
    private const HEADER_ROW = 4;
    private const FIRST_DATA_ROW = 5;

    public function create(array $dataset, int $mobilizationPercent = 75): string
    {
        @set_time_limit(1200);

        $directory = storage_path('app/exports');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible preparar la carpeta de exportaciones.');
        }

        $path = tempnam($directory, 'inteligencia_electoral_');
        if ($path === false) {
            throw new RuntimeException('No fue posible crear el archivo temporal de exportación.');
        }

        $spreadsheet = new Spreadsheet();
        $completed = false;

        try {
            $this->writeSummary($spreadsheet->getActiveSheet(), $dataset, $mobilizationPercent);
            $this->writeIndex($spreadsheet->createSheet(), $dataset);
            $this->writePriorities($spreadsheet->createSheet(), $dataset, $mobilizationPercent);
            $this->writeHistory($spreadsheet->createSheet(), $dataset);
            $this->writeMobilization($spreadsheet->createSheet(), $dataset, $mobilizationPercent);
            $this->writeSocioeconomic($spreadsheet->createSheet(), $dataset);
            $this->writeColonies($spreadsheet->createSheet(), $dataset);
            $this->writeSources($spreadsheet->createSheet(), $dataset);

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

    private function writeSummary(Worksheet $sheet, array $dataset, int $rate): void
    {
        $rows = [];
        foreach ($dataset['resumen_municipal'] as $year => $result) {
            $other = max(0, $result['votos_validos'] - $result['votos_morena']);
            $invalid = max(0, $result['votos_totales'] - $result['votos_validos']);
            $rows[] = [
                (int) $year,
                (int) $result['lista_nominal'],
                (int) $result['votos_totales'],
                (int) $result['votos_validos'],
                (int) $result['votos_morena'],
                $other,
                $invalid,
                max(0, $result['lista_nominal'] - $result['votos_totales']),
                (int) $result['secciones'],
                (int) $result['secciones_ganadas_morena'],
            ];
        }

        $this->writeTable(
            $sheet,
            'Resumen',
            'Resultados generales de Morelia',
            'Todos los valores son cantidades de personas o votos; no se usan porcentajes.',
            ['Año', 'Lista nominal', 'Votos emitidos', 'Votos válidos', 'Morena y aliados', 'Otros válidos', 'Nulos y no registrados', 'No votaron', 'Secciones', 'Ganadas por Morena'],
            $rows,
            [10, 17, 17, 16, 19, 16, 23, 15, 12, 21]
        );

        $operation = $dataset['operacion'];
        $nominalRoll = (int) ($dataset['resumen_municipal']['2024']['lista_nominal'] ?? 0);
        $indexed = collect($dataset['secciones'])->pluck('indice_electoral.iode')->filter(fn ($value) => $value !== null);
        $start = 11;
        $sheet->setCellValue("A{$start}", 'Base operativa, participación y zonas IODE');
        $sheet->getStyle("A{$start}:D{$start}")->applyFromArray($this->sectionHeaderStyle());
        $sheet->mergeCells("A{$start}:D{$start}");
        $metrics = [
            ['Registros utilizables', (int) $operation['registrados']],
            ['Afiliados confirmados', (int) $operation['afiliados_si']],
            ['Personas no afiliadas', (int) $operation['afiliados_no']],
            ["Podrían asistir al {$rate}% de la lista nominal", (int) round($nominalRoll * ($rate / 100))],
            ['Zona A · déficit', $indexed->filter(fn ($value) => $value < .75)->count()],
            ['Zona B · equilibrio', $indexed->filter(fn ($value) => $value >= .75 && $value <= 1.25)->count()],
            ['Zona C · sobreoferta', $indexed->filter(fn ($value) => $value > 1.25)->count()],
            ['Secciones sin IODE', (int) $dataset['indice_electoral']['secciones_sin_indice']],
        ];
        $sheet->fromArray($metrics, null, 'A'.($start + 1));
        $sheet->getStyle('B'.($start + 1).':B'.($start + count($metrics)))->getNumberFormat()->setFormatCode('#,##0');
    }

    private function writeIndex(Worksheet $sheet, array $dataset): void
    {
        $rows = collect($dataset['secciones'])
            ->filter(fn (array $section) => $section['indice_electoral'] !== null)
            ->sortBy(fn (array $section) => $section['indice_electoral']['iode'] ?? INF)
            ->map(function (array $section): array {
                $index = $section['indice_electoral'];
                $variables = $index['variables'];

                return [
                    (int) $section['seccion'],
                    $section['distrito_local'],
                    $section['distrito_federal'],
                    $index['ioe'],
                    $index['ide'],
                    $index['iode'],
                    $this->zone($index['iode']),
                    $index['estrategia'],
                    $variables['padron_electoral_2023'],
                    $variables['lista_nominal_2024'],
                    $variables['votos_validos_promedio'],
                    $variables['participacion_historica'],
                    $variables['votos_para_ganar_2024'],
                    $variables['porcentaje_morena_2024'],
                    $variables['variacion_participacion_2021_2024'],
                ];
            })->values()->all();

        $lastRow = $this->writeTable(
            $sheet,
            'Indice IODE',
            'Índice de oferta y demanda electoral por sección',
            'Zona A: menor de 0.75 · Zona B: de 0.75 a 1.25 · Zona C: mayor de 1.25.',
            ['Sección', 'Distrito local', 'Distrito federal', 'IOE', 'IDE', 'IODE', 'Zona', 'Lectura', 'Padrón 2023', 'Lista nominal 2024', 'Votos válidos promedio', 'Participación histórica', 'Votos para ganar 2024', 'Voto Morena 2024', 'Cambio participación 2021-2024'],
            $rows,
            [11, 15, 17, 11, 11, 11, 24, 37, 16, 19, 22, 21, 22, 19, 28]
        );
        $sheet->getStyle("D5:F{$lastRow}")->getNumberFormat()->setFormatCode('0.000');
        $sheet->getStyle("L5:L{$lastRow}")->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle("N5:O{$lastRow}")->getNumberFormat()->setFormatCode('0.0%');
        $this->colorZones($sheet, $rows, 7);
    }

    private function writePriorities(Worksheet $sheet, array $dataset, int $rate): void
    {
        $rows = collect($dataset['secciones'])->map(function (array $section) use ($rate): array {
            $result = $section['historial']['2024'];
            $operation = $section['operacion'];
            $index = $section['indice_electoral'];
            [$situation, $action] = $this->diagnosis($section);

            return [
                (int) $section['seccion'],
                $result ? ($result['morena_gano'] ? 'Victoria' : 'Derrota') : 'Sin resultado',
                $result['margen_morena'] ?? null,
                $index['iode'] ?? null,
                $index ? $this->zone($index['iode']) : 'Sin dato',
                (int) $operation['afiliados_si'],
                (int) round(($result['lista_nominal'] ?? 0) * ($rate / 100)),
                $result['lista_nominal'] ?? null,
                $situation,
                $action,
            ];
        })->sortBy(function (array $row): string {
            $iode = $row[3] ?? 999;

            return sprintf('%09.4f-%06d', $iode, $row[0]);
        })->values()->all();

        $lastRow = $this->writeTable(
            $sheet,
            'Prioridades',
            'Secciones para organizar el trabajo',
            "Se muestran números completos. La proyección de asistencia usa {$rate}% de la lista nominal 2024.",
            ['Sección', 'Resultado 2024', 'Votos de diferencia', 'IODE', 'Zona IODE', 'Afiliados', 'Podrían asistir', 'Lista nominal', 'Situación actual', 'Qué hace falta'],
            $rows,
            [11, 18, 20, 12, 24, 14, 18, 17, 25, 23]
        );
        $sheet->getStyle("D5:D{$lastRow}")->getNumberFormat()->setFormatCode('0.000');
        $this->colorZones($sheet, $rows, 5);
    }

    private function writeHistory(Worksheet $sheet, array $dataset): void
    {
        $rows = [];
        foreach ($dataset['secciones'] as $section) {
            foreach (['2018', '2021', '2024'] as $year) {
                $result = $section['historial'][$year];
                if ($result === null) {
                    continue;
                }
                $other = max(0, $result['votos_validos'] - $result['votos_morena']);
                $rows[] = [
                    (int) $section['seccion'],
                    (int) $year,
                    $section['distrito_local'],
                    $section['distrito_federal'],
                    (int) $result['lista_nominal'],
                    (int) $result['votos_totales'],
                    (int) $result['votos_validos'],
                    (int) $result['votos_morena'],
                    $other,
                    max(0, $result['votos_totales'] - $result['votos_validos']),
                    max(0, $result['lista_nominal'] - $result['votos_totales']),
                    $result['ganador'],
                    (int) $result['margen_morena'],
                ];
            }
        }

        $this->writeTable(
            $sheet,
            'Historial electoral',
            'Resultados históricos por sección',
            'Cantidades agregadas por sección. Las casillas especiales están excluidas.',
            ['Sección', 'Año', 'Distrito local', 'Distrito federal', 'Lista nominal', 'Votos emitidos', 'Votos válidos', 'Morena y aliados', 'Otros válidos', 'Nulos y no registrados', 'No votaron', 'Ganador', 'Diferencia Morena'],
            $rows,
            [11, 10, 15, 17, 16, 17, 16, 19, 16, 23, 15, 24, 19]
        );
    }

    private function writeMobilization(Worksheet $sheet, array $dataset, int $rate): void
    {
        $rows = collect($dataset['secciones'])->map(function (array $section) use ($rate): array {
            $operation = $section['operacion'];
            $result = $section['historial']['2024'];

            return [
                (int) $section['seccion'],
                $section['distrito_local'],
                $section['distrito_federal'],
                (int) $operation['registrados'],
                (int) $operation['afiliados_si'],
                (int) $operation['afiliados_no'],
                $rate,
                (int) round(($result['lista_nominal'] ?? 0) * ($rate / 100)),
                $result['lista_nominal'] ?? null,
                $result['votos_morena'] ?? null,
            ];
        })->values()->all();

        $this->writeTable(
            $sheet,
            'Movilizacion',
            'Lista nominal y escenario de participación',
            "Escenario elegido: {$rate}% de la lista nominal 2024.",
            ['Sección', 'Distrito local', 'Distrito federal', 'Registros', 'Afiliados confirmados', 'No afiliados', 'Tasa usada', 'Podrían asistir', 'Lista nominal 2024', 'Votos Morena 2024'],
            $rows,
            [11, 15, 17, 14, 22, 16, 14, 18, 19, 20]
        );
    }

    private function writeSocioeconomic(Worksheet $sheet, array $dataset): void
    {
        $rows = collect($dataset['secciones'])->filter(fn (array $section) => $section['socioeconomico'] !== null)
            ->map(function (array $section): array {
                $socio = $section['socioeconomico'];
                $indicators = $socio['indicadores'];

                return [
                    (int) $section['seccion'],
                    $section['distrito_local'],
                    $section['distrito_federal'],
                    $socio['grado_rezago'],
                    $indicators['educacion_basica_incompleta'],
                    $indicators['sin_derechohabiencia'],
                    $indicators['hacinamiento'],
                    $indicators['sin_internet'],
                    $indicators['sin_computadora'],
                    $socio['poblacion_representada'],
                    $socio['agebs'],
                    $socio['cobertura_territorial'],
                ];
            })->values()->all();

        $lastRow = $this->writeTable(
            $sheet,
            'Condiciones 2020',
            'Condiciones de vida por sección',
            'Indicadores agregados del Censo 2020 y rezago social; no describen personas individuales.',
            ['Sección', 'Distrito local', 'Distrito federal', 'Grado de rezago', 'Educación básica incompleta (%)', 'Sin servicios de salud (%)', 'Hacinamiento (%)', 'Sin internet (%)', 'Sin computadora (%)', 'Población representada', 'AGEB', 'Cobertura territorial'],
            $rows,
            [11, 15, 17, 19, 31, 27, 19, 19, 23, 23, 11, 22]
        );
        $sheet->getStyle("E5:I{$lastRow}")->getNumberFormat()->setFormatCode('0.0');
        $sheet->getStyle("L5:L{$lastRow}")->getNumberFormat()->setFormatCode('0.0%');
    }

    private function writeColonies(Worksheet $sheet, array $dataset): void
    {
        $rows = collect($dataset['secciones'])->map(function (array $section): array {
            $colonies = $section['colonias'];

            return [
                (int) $section['seccion'],
                $section['distrito_local'],
                $section['distrito_federal'],
                implode(', ', $colonies['nombres']),
                $colonies['total_encontradas'],
                $colonies['fuente'] ?? 'Sin dato',
                $colonies['es_catalogo_completo'] ? 'Sí' : 'No',
            ];
        })->values()->all();

        $lastRow = $this->writeTable(
            $sheet,
            'Colonias',
            'Colonias relacionadas con cada sección',
            'Son referencias territoriales; no representan límites oficiales ni un catálogo completo.',
            ['Sección', 'Distrito local', 'Distrito federal', 'Colonias relacionadas', 'Nombres encontrados', 'Fuente', 'Catálogo completo'],
            $rows,
            [11, 15, 17, 65, 20, 38, 19]
        );
        $sheet->getStyle("D5:D{$lastRow}")->getAlignment()->setWrapText(true);
    }

    private function writeSources(Worksheet $sheet, array $dataset): void
    {
        $rows = [];
        foreach ($dataset['metadata']['fuentes'] as $source) {
            $rows[] = [$source['anio'], $source['institucion'], $source['descripcion'], $source['url']];
        }
        $rows[] = ['2020', 'CONEVAL', 'Grado de Rezago Social por AGEB urbana', 'https://www.coneval.org.mx/Medicion/IRS/Paginas/Rezago_social_AGEB_2020.aspx'];
        $rows[] = ['2020', 'INEGI', 'Marco Geoestadístico del Censo 2020', 'https://www.inegi.org.mx/app/biblioteca/ficha.html?upc=889463807469'];

        $lastRow = $this->writeTable(
            $sheet,
            'Fuentes y metodo',
            'Fuentes y método de cálculo',
            $dataset['indice_electoral']['metodo'],
            ['Año', 'Institución', 'Descripción', 'Enlace'],
            $rows,
            [12, 30, 60, 80]
        );
        for ($row = self::FIRST_DATA_ROW; $row <= $lastRow; $row++) {
            $url = $sheet->getCell("D{$row}")->getValue();
            if ($url) {
                $sheet->getCell("D{$row}")->getHyperlink()->setUrl($url);
                $sheet->getStyle("D{$row}")->getFont()->getColor()->setRGB('2458A6');
                $sheet->getStyle("D{$row}")->getFont()->setUnderline(true);
            }
        }
    }

    private function writeTable(Worksheet $sheet, string $title, string $heading, string $note, array $headers, array $rows, array $widths): int
    {
        $sheet->setTitle($title);
        $sheet->setShowGridlines(false);
        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', $heading);
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A2', $note);
        $sheet->setCellValue('A3', 'Generado el '.now('America/Mexico_City')->format('d/m/Y H:i'));
        $sheet->fromArray($headers, null, 'A'.self::HEADER_ROW);
        if ($rows) {
            $sheet->fromArray($rows, null, 'A'.self::FIRST_DATA_ROW);
        }

        $lastRow = max(self::HEADER_ROW, self::FIRST_DATA_ROW + count($rows) - 1);
        $sheet->setAutoFilter('A'.self::HEADER_ROW.':'.$lastColumn.$lastRow);
        $sheet->freezePane('A'.self::FIRST_DATA_ROW);
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '7A0019']],
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle("A2:{$lastColumn}3")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F8EDF2']],
            'font' => ['color' => ['rgb' => '5C2034']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getStyle('A'.self::HEADER_ROW.':'.$lastColumn.self::HEADER_ROW)->applyFromArray($this->sectionHeaderStyle());
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(34);
        if ($lastRow >= self::FIRST_DATA_ROW) {
            $sheet->getStyle('A'.self::FIRST_DATA_ROW.':'.$lastColumn.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A'.self::FIRST_DATA_ROW.':'.$lastColumn.$lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }
        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
        }

        return $lastRow;
    }

    private function sectionHeaderStyle(): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '252E3B']],
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D91785']]],
        ];
    }

    private function colorZones(Worksheet $sheet, array $rows, int $zoneColumn): void
    {
        $column = Coordinate::stringFromColumnIndex($zoneColumn);
        foreach ($rows as $offset => $row) {
            $zone = (string) ($row[$zoneColumn - 1] ?? '');
            $color = str_starts_with($zone, 'Zona A') ? 'FFF2CC' : (str_starts_with($zone, 'Zona B') ? 'D9E8F5' : (str_starts_with($zone, 'Zona C') ? 'C9EFDF' : 'EEF0F3'));
            $sheet->getStyle($column.(self::FIRST_DATA_ROW + $offset))->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $color]],
                'font' => ['bold' => true],
            ]);
        }
    }

    private function zone(?float $iode): string
    {
        if ($iode === null) {
            return 'Sin dato';
        }
        if ($iode < .75) {
            return 'Zona A · déficit';
        }
        if ($iode <= 1.25) {
            return 'Zona B · equilibrio';
        }

        return 'Zona C · sobreoferta';
    }

    private function diagnosis(array $section): array
    {
        $result = $section['historial']['2024'];
        $yes = (int) $section['operacion']['afiliados_si'];
        $closeLoss = $result && !$result['morena_gano'] && abs((int) $result['margen_morena']) <= 100;

        $situation = $yes === 0 ? 'Sin afiliados' : ($yes <= 9 ? 'Muy pocos afiliados' : ($yes >= 100 ? '100 afiliados o más' : ($yes >= 30 ? '30 afiliados o más' : 'En crecimiento')));
        $action = !$result ? 'Revisar la información' : ($yes === 0 ? 'Comenzar el registro' : ($closeLoss ? 'Atender primero' : ($yes <= 9 ? 'Sumar más afiliados' : ($yes >= 100 ? 'Mantener el contacto' : ($result['morena_gano'] ? 'Cuidar la ventaja' : 'Sumar más afiliados')))));

        return [$situation, $action];
    }
}
