<?php

namespace App\Services;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class EliasAfiliadosImportService
{
    private const PLACEHOLDER_PHONES = [
        '0000000000',
        '1111111111',
        '9999999999',
    ];

    public function scan(string $directory, array $catalogBySection): array
    {
        $directory = rtrim($directory, '/\\');
        if (!is_dir($directory)) {
            throw new RuntimeException("No existe el directorio de importación: {$directory}");
        }

        $files = glob($directory.DIRECTORY_SEPARATOR.'*.xlsx') ?: [];
        natsort($files);
        $files = array_values($files);

        if ($files === []) {
            throw new RuntimeException("No se encontraron archivos .xlsx en {$directory}");
        }

        $result = [
            'files' => array_map('basename', $files),
            'rows' => [],
            'rejections' => [],
            'stats' => [
                'source_rows' => 0,
                'blank_rows' => 0,
                'repeated_headers' => 0,
                'invalid_phones' => 0,
                'missing_phones' => 0,
                'source_duplicates' => 0,
            ],
        ];
        $fingerprints = [];

        foreach ($files as $file) {
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            if (method_exists($reader, 'setReadEmptyCells')) {
                $reader->setReadEmptyCells(false);
            }
            $workbook = $reader->load($file);

            foreach ($workbook->getWorksheetIterator() as $sheet) {
                $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
                $highestRow = $sheet->getHighestDataRow();
                $headers = $this->headers($sheet, $highestColumn);
                $schema = $this->schema($headers);

                if ($schema === null) {
                    $workbook->disconnectWorksheets();
                    throw new RuntimeException(sprintf(
                        'La hoja "%s" de %s no tiene un formato reconocido.',
                        $sheet->getTitle(),
                        basename($file)
                    ));
                }

                for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
                    $raw = $this->readRow($sheet, $headers, $schema, $rowNumber);

                    if ($this->isBlank($raw)) {
                        $result['stats']['blank_rows']++;
                        continue;
                    }
                    if ($this->isRepeatedHeader($raw)) {
                        $result['stats']['repeated_headers']++;
                        continue;
                    }

                    $result['stats']['source_rows']++;
                    $prepared = $this->prepareRow($raw, $catalogBySection);
                    if ($prepared['reason'] !== null) {
                        $result['rejections'][] = [
                            'file' => basename($file),
                            'sheet' => $sheet->getTitle(),
                            'row' => $rowNumber,
                            'reason' => $prepared['reason'],
                            'name' => $prepared['name'],
                            'phone' => $prepared['phone'],
                            'section' => $prepared['section'],
                        ];
                        continue;
                    }

                    if ($prepared['phone_state'] === 'missing') {
                        $result['stats']['missing_phones']++;
                    } elseif ($prepared['phone_state'] === 'invalid') {
                        $result['stats']['invalid_phones']++;
                    }

                    $payload = $prepared['payload'];
                    $fingerprint = $this->sourceFingerprint($payload);
                    if (isset($fingerprints[$fingerprint])) {
                        $result['stats']['source_duplicates']++;
                        continue;
                    }
                    $fingerprints[$fingerprint] = true;

                    $payload['_source_file'] = basename($file);
                    $payload['_source_sheet'] = $sheet->getTitle();
                    $payload['_source_row'] = $rowNumber;
                    $payload['_source_id'] = $this->cleanText($raw['source_id']);
                    $payload['_phone_state'] = $prepared['phone_state'];
                    $result['rows'][] = $payload;
                }
            }

            $workbook->disconnectWorksheets();
            unset($workbook, $reader);
            gc_collect_cycles();
        }

        return $result;
    }

    public function identityKey(array $row): string
    {
        $name = $this->keyText(implode(' ', array_filter([
            $row['nombre'] ?? null,
            $row['apellido_paterno'] ?? null,
            $row['apellido_materno'] ?? null,
        ])));
        $section = (string) (int) $this->digits($row['seccion'] ?? '');
        $municipality = str_pad((string) ($row['cve_mun'] ?? ''), 3, '0', STR_PAD_LEFT);
        $phone = $this->normalizePhone($row['telefono'] ?? null)['value'];

        if ($phone) {
            return $name.'|'.$phone.'|'.$municipality.'|'.$section;
        }

        return implode('|', [
            $name,
            'SIN-TELEFONO',
            $municipality,
            $section,
            $this->keyText($row['colonia'] ?? ''),
            $this->keyText($row['calle'] ?? ''),
            $this->keyText($row['numero_ext'] ?? ''),
        ]);
    }

    private function headers($sheet, int $highestColumn): array
    {
        $headers = [];
        for ($column = 1; $column <= $highestColumn; $column++) {
            $key = $this->keyText($sheet->getCell([$column, 1])->getCalculatedValue());
            if ($key !== '') {
                $headers[$key] = $column;
            }
        }
        return $headers;
    }

    private function schema(array $headers): ?string
    {
        if (isset($headers['NOMBRE'], $headers['TELEFONO'], $headers['SECCION'])) {
            return 'survey';
        }
        if (isset($headers['NOMBRE'], $headers['APELLIDO PATERNO'], $headers['APELLIDO MATERNO'], $headers['IDSECC'])) {
            return 'registry';
        }
        return null;
    }

    private function readRow($sheet, array $headers, string $schema, int $row): array
    {
        $value = function (string $header) use ($sheet, $headers, $row) {
            return isset($headers[$header])
                ? $sheet->getCell([$headers[$header], $row])->getCalculatedValue()
                : null;
        };

        if ($schema === 'registry') {
            return [
                'name' => implode(' ', array_filter([
                    $this->cleanText($value('NOMBRE')),
                    $this->cleanText($value('APELLIDO PATERNO')),
                    $this->cleanText($value('APELLIDO MATERNO')),
                ])),
                'phone' => null,
                'section' => $value('IDSECC'),
                'municipality' => $value('MUNICIPIO'),
                'colony' => $value('COLONIA'),
                'street' => $value('CALLE'),
                'external_number' => $value('NUM EXT'),
                'internal_number' => $value('NUM INT'),
                'postal_code' => $value('CODPOS'),
                'source_id' => null,
            ];
        }

        return [
            'name' => $value('NOMBRE'),
            'phone' => $value('TELEFONO'),
            'section' => $value('SECCION'),
            'municipality' => $value('MUNICIPIO'),
            'colony' => $value('COLONIA'),
            'street' => $value('CALLE'),
            'external_number' => $value('NO EXT'),
            'internal_number' => $value('NO INT'),
            'postal_code' => $value('CP'),
            'source_id' => $value('ID'),
        ];
    }

    private function prepareRow(array $raw, array $catalogBySection): array
    {
        $name = Str::upper(Str::ascii($this->cleanText($raw['name'])));
        $phone = $this->normalizePhone($raw['phone']);
        $sectionDigits = $this->digits($raw['section']);
        $section = $sectionDigits === '' ? '' : (string) (int) $sectionDigits;

        $reason = null;
        if ($name === '') {
            $reason = 'nombre vacío';
        } elseif (mb_strlen($name) > 120) {
            $reason = 'nombre mayor a 120 caracteres';
        } elseif ($section === '' || (int) $section <= 0 || strlen($section) > 6) {
            $reason = 'sección vacía o inválida';
        }

        $geography = null;
        if ($reason === null) {
            $geography = $this->resolveGeography(
                $catalogBySection[$section] ?? [],
                $raw['municipality']
            );
            if ($geography === null) {
                $reason = 'sección inexistente o ambigua en el catálogo';
            }
        }

        $payload = null;
        if ($reason === null) {
            $payload = [
                'nombre' => $name,
                'apellido_paterno' => null,
                'apellido_materno' => null,
                'telefono' => $phone['value'],
                'municipio' => $geography['municipio'],
                'cve_mun' => $geography['cve_mun'],
                'colonia' => $this->nullableText($raw['colony'], 150),
                'calle' => $this->nullableText($raw['street'], 150),
                'numero_ext' => $this->nullableText($raw['external_number'], 20, true),
                'numero_int' => $this->nullableText($raw['internal_number'], 20, true),
                'cp' => $this->postalCode($raw['postal_code']),
                'seccion' => $section,
                'distrito_local' => (int) $geography['distrito_local'],
                'distrito_federal' => (int) $geography['distrito_federal'],
            ];
        }

        return [
            'reason' => $reason,
            'name' => $name,
            'phone' => $phone['value'] ?: $this->cleanText($raw['phone']),
            'phone_state' => $phone['state'],
            'section' => $section ?: $this->cleanText($raw['section']),
            'payload' => $payload,
        ];
    }

    private function resolveGeography(array $candidates, mixed $sourceMunicipality): ?array
    {
        $unique = [];
        foreach ($candidates as $candidate) {
            $key = str_pad((string) $candidate['cve_mun'], 3, '0', STR_PAD_LEFT)
                .'|'.$this->keyText($candidate['municipio']);
            $unique[$key] = $candidate;
        }
        $candidates = array_values($unique);

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        $municipality = $this->keyText($sourceMunicipality);
        $matches = array_values(array_filter(
            $candidates,
            fn (array $candidate): bool => $this->keyText($candidate['municipio']) === $municipality
        ));

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function normalizePhone(mixed $value): array
    {
        $digits = $this->digits($value);
        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            $digits = substr($digits, 3);
        } elseif (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '') {
            return ['value' => null, 'state' => 'missing'];
        }
        if (strlen($digits) !== 10 || in_array($digits, self::PLACEHOLDER_PHONES, true)) {
            return ['value' => null, 'state' => 'invalid'];
        }

        return ['value' => $digits, 'state' => 'valid'];
    }

    private function sourceFingerprint(array $payload): string
    {
        return hash('sha256', json_encode([
            $this->keyText($payload['nombre']),
            $payload['telefono'],
            $payload['cve_mun'],
            $payload['seccion'],
            $this->keyText($payload['colonia']),
            $this->keyText($payload['calle']),
            $this->keyText($payload['numero_ext']),
            $this->keyText($payload['numero_int']),
            $payload['cp'],
        ], JSON_UNESCAPED_UNICODE));
    }

    private function isBlank(array $row): bool
    {
        return $this->cleanText($row['name']) === ''
            && $this->cleanText($row['phone']) === ''
            && $this->cleanText($row['section']) === '';
    }

    private function isRepeatedHeader(array $row): bool
    {
        $name = $this->keyText($row['name']);
        $phone = $this->keyText($row['phone']);
        $section = $this->keyText($row['section']);

        return $name === 'NOMBRE'
            || ($phone === 'TELEFONO' && in_array($section, ['', 'SECCION'], true));
    }

    private function nullableText(mixed $value, int $maxLength, bool $removeNoNumber = false): ?string
    {
        $value = $this->cleanText($value);
        if ($value === '') {
            return null;
        }
        if ($removeNoNumber && in_array($this->keyText($value), ['S N', 'N S', 'SN', 'NS', 'SIN'], true)) {
            return null;
        }
        return mb_substr($value, 0, $maxLength);
    }

    private function postalCode(mixed $value): ?string
    {
        $digits = $this->digits($value);
        return strlen($digits) === 5 ? $digits : null;
    }

    private function cleanText(mixed $value): string
    {
        if (is_float($value) || is_int($value)) {
            $value = sprintf('%.0f', $value);
        }
        $value = preg_replace('/[\x{00A0}\x{2007}\x{202F}]/u', ' ', (string) ($value ?? ''));
        return preg_replace('/\s+/u', ' ', trim($value));
    }

    private function keyText(mixed $value): string
    {
        $value = Str::upper(Str::ascii($this->cleanText($value)));
        return trim(preg_replace('/[^A-Z0-9]+/', ' ', $value));
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', $this->cleanText($value));
    }
}
