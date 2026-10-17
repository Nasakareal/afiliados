<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Uso: php scripts/import_morelia_electoral_roll.php <archivo.xlsx> <salida.json>\n");
    exit(1);
}

[$script, $source, $target] = $argv;

if (! is_file($source)) {
    fwrite(STDERR, "No existe el archivo fuente: {$source}\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($source) !== true) {
    fwrite(STDERR, "No se pudo abrir el XLSX.\n");
    exit(1);
}

$sharedStrings = [];
$sharedXml = $zip->getFromName('xl/sharedStrings.xml');
if ($sharedXml !== false) {
    $shared = simplexml_load_string($sharedXml);
    foreach ($shared->si as $item) {
        $parts = [];
        if (isset($item->t)) {
            $parts[] = (string) $item->t;
        }
        foreach ($item->r as $run) {
            $parts[] = (string) $run->t;
        }
        $sharedStrings[] = implode('', $parts);
    }
}

$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
$zip->close();
if ($sheetXml === false) {
    fwrite(STDERR, "No se encontró la primera hoja del XLSX.\n");
    exit(1);
}

$sheet = simplexml_load_string($sheetXml);
$rows = [];
foreach ($sheet->sheetData->row as $row) {
    if ((int) $row['r'] === 1) {
        continue;
    }

    $values = [];
    foreach ($row->c as $cell) {
        $reference = (string) $cell['r'];
        preg_match('/^[A-Z]+/', $reference, $match);
        $column = $match[0] ?? '';
        $value = (string) $cell->v;
        if ((string) $cell['t'] === 's') {
            $value = $sharedStrings[(int) $value] ?? '';
        }
        $values[$column] = trim($value);
    }

    if (($values['A'] ?? '') !== '16' || strtoupper($values['F'] ?? '') !== 'MORELIA') {
        continue;
    }

    $section = (int) ($values['G'] ?? 0);
    if ($section <= 0) {
        continue;
    }

    $rows[(string) $section] = [
        'padron_electoral' => (int) ($values['K'] ?? 0),
        'lista_nominal' => (int) ($values['O'] ?? 0),
    ];
}

ksort($rows, SORT_NUMERIC);
$payload = [
    'metadata' => [
        'fuente' => 'INE, Datos abiertos del padrón electoral y lista nominal por edad y sexo',
        'archivo' => basename($source),
        'corte' => '2023-12-28',
        'entidad' => 16,
        'municipio' => 54,
        'nota' => 'El padrón electoral se usa como proxy operativo de población en edad de votar; no equivale a población censal de 18 años y más.',
    ],
    'secciones' => $rows,
];

$encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
if (file_put_contents($target, $encoded) === false) {
    fwrite(STDERR, "No se pudo escribir: {$target}\n");
    exit(1);
}

fwrite(STDOUT, sprintf("Importadas %d secciones de Morelia.\n", count($rows)));
