<?php

namespace App\Services;

class ElectoralPriorityColonies
{
    public function enrich(array $dataset): array
    {
        $reference = $this->reference();
        $rows = collect($reference['filas'] ?? [])
            ->map(function (array $row): array {
                $priority = (int) $row['prioridad'];

                return array_merge($row, [
                    'seccion' => (int) $row['seccion'],
                    'distrito_local' => (int) $row['distrito_local'],
                    'prioridad' => $priority,
                    'prioridad_etiqueta' => $this->priorityLabel($priority),
                    'lista_nominal_2021' => (int) $row['lista_nominal_2021'],
                    'lista_nominal_2024' => (int) $row['lista_nominal_2024'],
                ]);
            })
            ->values();
        $bySection = $rows->keyBy(fn (array $row): string => (string) $row['seccion']);

        foreach ($dataset['secciones'] as &$section) {
            $section['colonia_prioritaria'] = $bySection->get((string) $section['seccion']);
        }
        unset($section);

        $dataset['colonias_prioritarias'] = [
            'metadata' => $reference['metadata'] ?? [],
            'resumen' => [
                'total' => $rows->count(),
                'alta' => $rows->where('prioridad', 3)->count(),
                'media' => $rows->where('prioridad', 2)->count(),
                'baja' => $rows->where('prioridad', 1)->count(),
            ],
            'filas' => $rows->all(),
        ];

        return $dataset;
    }

    private function reference(): array
    {
        $path = database_path('data/electoral/morelia_priority_colonies_reference.json');

        if (! is_file($path)) {
            return ['metadata' => [], 'filas' => []];
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function priorityLabel(int $priority): string
    {
        return [
            3 => 'Alta',
            2 => 'Media',
            1 => 'Baja',
        ][$priority] ?? 'Sin clasificar';
    }
}
