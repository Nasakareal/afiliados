<?php

namespace App\Services;

class SocioeconomicSectionLayer
{
    public function enrich(array $dataset): array
    {
        $layer = $this->layer();
        $sections = $layer['secciones'] ?? [];

        foreach ($dataset['secciones'] as &$section) {
            $section['socioeconomico'] = $sections[(string) $section['seccion']] ?? null;
        }
        unset($section);

        $withData = collect($dataset['secciones'])->filter(
            fn (array $section): bool => $section['socioeconomico'] !== null
        );

        $dataset['socioeconomico'] = [
            'metadata' => $layer['metadata'] ?? [],
            'secciones_con_datos' => $withData->count(),
            'secciones_sin_datos' => count($dataset['secciones']) - $withData->count(),
            'cobertura_alta' => $withData->filter(
                fn (array $section): bool => $section['socioeconomico']['cobertura_territorial'] >= .8
            )->count(),
        ];

        return $dataset;
    }

    private function layer(): array
    {
        $path = database_path('data/electoral/morelia_socioeconomic_sections_2020.json');
        if (! is_file($path)) {
            return ['metadata' => [], 'secciones' => []];
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
