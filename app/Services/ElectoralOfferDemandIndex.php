<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ElectoralOfferDemandIndex
{
    public function enrich(array $dataset): array
    {
        $roll = $this->electoralRoll();
        $eligible = [];

        foreach ($dataset['secciones'] as $section) {
            $key = (string) $section['seccion'];
            $history = collect($section['historial'])->filter();
            $current = $section['historial']['2024'];
            $previous = $section['historial']['2021'];

            if ($current === null || $previous === null || ! isset($roll[$key])) {
                continue;
            }

            $eligible[$key] = [
                'padron_electoral_2023' => $roll[$key]['padron_electoral'],
                'lista_nominal_2024' => $current['lista_nominal'],
                'votos_validos_promedio' => $history->avg('votos_validos'),
                'participacion_historica' => $history->avg('participacion'),
                'votos_para_ganar_2024' => max(1, $current['votos_morena'] - $current['margen_morena'] + 1),
                'porcentaje_morena_2024' => $current['porcentaje_morena'],
                'variacion_participacion_2021_2024' => $current['participacion'] - $previous['participacion'],
            ];
        }

        $percentiles = [];
        foreach (array_keys(reset($eligible) ?: []) as $variable) {
            $percentiles[$variable] = $this->percentileRanks(
                collect($eligible)->mapWithKeys(fn (array $values, $section): array => [$section => $values[$variable]])
            );
        }

        $indexes = [];
        foreach ($eligible as $section => $values) {
            $scores = collect($percentiles)->mapWithKeys(
                fn (array $ranks, string $variable): array => [$variable => $ranks[$section]]
            )->all();

            $offer = (
                .10 * $scores['padron_electoral_2023']
                + .10 * $scores['votos_validos_promedio']
                + .15 * $scores['lista_nominal_2024']
                + .15 * $scores['participacion_historica']
            );
            $demand = (
                .30 * $scores['votos_para_ganar_2024']
                + .10 * (1 - $scores['porcentaje_morena_2024'])
                + .10 * (1 - $scores['variacion_participacion_2021_2024'])
            );

            $ratio = $demand > 0 ? $offer / $demand : null;

            $indexes[$section] = [
                'ioe' => round($offer, 4),
                'ide' => round($demand, 4),
                'iode' => $ratio === null ? null : round($ratio, 4),
                'variables' => [
                    'padron_electoral_2023' => (int) $values['padron_electoral_2023'],
                    'votos_validos_promedio' => (int) round($values['votos_validos_promedio']),
                    'lista_nominal_2024' => (int) $values['lista_nominal_2024'],
                    'participacion_historica' => round($values['participacion_historica'], 6),
                    'votos_para_ganar_2024' => (int) $values['votos_para_ganar_2024'],
                    'porcentaje_morena_2024' => round($values['porcentaje_morena_2024'], 6),
                    'variacion_participacion_2021_2024' => round($values['variacion_participacion_2021_2024'], 6),
                ],
            ];
        }

        foreach ($indexes as &$index) {
            $index['estrategia'] = $this->strategy($index['iode']);
        }
        unset($index);

        foreach ($dataset['secciones'] as &$section) {
            $section['indice_electoral'] = $indexes[(string) $section['seccion']] ?? null;
        }
        unset($section);

        $dataset['indice_electoral'] = [
            'secciones_calculadas' => count($indexes),
            'secciones_sin_indice' => count($dataset['secciones']) - count($indexes),
            'metodo' => 'Cada variable se convierte a un rango relativo de 0 a 1 entre secciones comparables. Se aplican directamente las ponderaciones: IOE suma hasta 0.50 e IDE suma hasta 0.50. IODE es la división directa IOE/IDE.',
            'proxy' => 'Para saber cuántas personas pueden votar se usa el padrón del INE de diciembre de 2023. Este dato no es un conteo de población.',
            'pesos' => [
                'ioe' => [
                    'padron_electoral_2023' => 10,
                    'votos_validos_promedio' => 10,
                    'lista_nominal_2024' => 15,
                    'participacion_historica' => 15,
                ],
                'ide' => [
                    'votos_para_ganar_2024' => 30,
                    'porcentaje_morena_2024_invertido' => 10,
                    'variacion_participacion_2021_2024_invertida' => 10,
                ],
            ],
        ];

        return $dataset;
    }

    private function electoralRoll(): array
    {
        $path = database_path('data/electoral/morelia_padron_2023.json');
        if (! is_file($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)['secciones'] ?? [];
    }

    private function percentileRanks(Collection $values): array
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        return $values->mapWithKeys(function ($value, $key) use ($sorted, $count): array {
            if ($count <= 1) {
                return [$key => .5];
            }

            $less = $sorted->filter(fn ($candidate): bool => $candidate < $value)->count();
            $equal = $sorted->filter(fn ($candidate): bool => $candidate == $value)->count();

            return [$key => ($less + (($equal - 1) / 2)) / ($count - 1)];
        })->all();
    }

    private function strategy(?float $ratio): string
    {
        if ($ratio === null) {
            return 'No se puede calcular';
        }
        if ($ratio > 1.25) {
            return 'Zona C · sobreoferta · no prioritaria';
        }
        if ($ratio >= .75) {
            return 'Zona B · equilibrio · seguimiento';
        }

        return 'Zona A · déficit · prioritaria';
    }
}
