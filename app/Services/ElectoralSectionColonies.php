<?php

namespace App\Services;

use App\Support\LocalDistrictAccess;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ElectoralSectionColonies
{
    private const MAX_NAMES = 8;

    public function enrich(array $dataset, ?User $user = null): array
    {
        $validSections = collect($dataset['secciones'])
            ->pluck('seccion')
            ->map(fn ($section): string => (string) $section)
            ->all();

        $referenceData = $this->reference();
        $reference = $referenceData['secciones'];
        $referenceSources = $referenceData['fuentes_por_seccion'];
        $registered = $this->registeredBySection($validSections, $user);
        $sectionsWithNames = 0;

        foreach ($dataset['secciones'] as &$section) {
            $key = (string) $section['seccion'];
            $referenceNames = $reference[$key] ?? [];
            $registeredNames = $registered[$key] ?? [];
            // Cuando el estudio contiene la sección, se usa esa relación antes que
            // los nombres de captura libre para no mezclar errores de domicilio.
            $availableNames = $referenceNames ?: $registeredNames;
            $allNames = $this->uniqueNames($availableNames);
            $names = $allNames->take(self::MAX_NAMES)->values();

            if ($names->isNotEmpty()) {
                $sectionsWithNames++;
            }

            $section['colonias'] = [
                'nombres' => $names->all(),
                'total_encontradas' => $allNames->count(),
                'fuente' => $referenceNames
                    ? ($referenceSources[$key] ?? 'Estudio electoral 2024')
                    : ($registeredNames ? 'Registros de la base' : null),
                'es_catalogo_completo' => false,
            ];
        }
        unset($section);

        $dataset['colonias'] = [
            'secciones_con_nombres' => $sectionsWithNames,
            'secciones_sin_nombres' => count($dataset['secciones']) - $sectionsWithNames,
            'limite_mostrado_por_seccion' => self::MAX_NAMES,
            'nota' => 'Los nombres ayudan a ubicar la sección. No representan límites oficiales ni un catálogo completo de colonias.',
        ];

        return $dataset;
    }

    private function registeredBySection(array $validSections, ?User $user): array
    {
        $query = DB::table('afiliados as a')
            ->whereNull('a.deleted_at')
            ->where(function ($municipality): void {
                $municipality
                    ->where('a.cve_mun', '053')
                    ->orWhereRaw('UPPER(TRIM(a.municipio)) = ?', ['MORELIA']);
            })
            ->whereIn('a.seccion', $validSections)
            ->whereNotNull('a.colonia')
            ->where('a.colonia', '<>', '')
            ->select('a.seccion', 'a.colonia')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('a.seccion', 'a.colonia');

        LocalDistrictAccess::scope($query, 'a.distrito_local', $user);

        return $query->get()
            ->filter(fn ($row): bool => $this->isUsefulName((string) $row->colonia))
            ->groupBy(fn ($row): string => (string) ((int) $row->seccion))
            ->map(function (Collection $rows): array {
                return $rows
                    ->groupBy(fn ($row): string => $this->key((string) $row->colonia))
                    ->map(function (Collection $variants): array {
                        $best = $variants->sortByDesc(fn ($row): int => (int) $row->total)->first();

                        return [
                            'nombre' => $this->formatName((string) $best->colonia),
                            'total' => (int) $variants->sum('total'),
                        ];
                    })
                    ->sortByDesc('total')
                    ->pluck('nombre')
                    ->values()
                    ->all();
            })
            ->all();
    }

    private function reference(): array
    {
        $path = database_path('data/electoral/morelia_section_colonies_reference.json');

        if (!is_file($path)) {
            return ['secciones' => [], 'fuentes_por_seccion' => []];
        }

        $contents = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return [
            'secciones' => $contents['secciones'] ?? [],
            'fuentes_por_seccion' => $contents['metadata']['fuentes_por_seccion'] ?? [],
        ];
    }

    private function uniqueNames(array $names): Collection
    {
        return collect($names)
            ->filter(fn ($name): bool => $this->isUsefulName((string) $name))
            ->unique(fn ($name): string => $this->key((string) $name));
    }

    private function key(string $name): string
    {
        return preg_replace('/\s+/u', ' ', trim(Str::upper(Str::ascii($name)))) ?? '';
    }

    private function formatName(string $name): string
    {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

        return str_replace(
            [' De ', ' Del ', ' La ', ' Las ', ' Los ', ' Y '],
            [' de ', ' del ', ' la ', ' las ', ' los ', ' y '],
            $name
        );
    }

    private function isUsefulName(string $name): bool
    {
        $key = $this->key($name);

        return mb_strlen($key) >= 3
            && preg_match('/[A-Z]/', $key) === 1
            && !in_array($key, ['N/A', 'NA', 'NINGUNA', 'NINGUNO', 'NO APLICA', 'SIN COLONIA', 'SIN DATO', 'DESCONOCIDA'], true);
    }
}
