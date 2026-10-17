<?php

namespace Tests\Unit;

use Tests\TestCase;

class ElectoralIntelligenceDataTest extends TestCase
{
    public function test_morelia_electoral_dataset_is_complete_and_internally_consistent(): void
    {
        $path = database_path('data/electoral/morelia_municipal_sections.json');
        $this->assertFileExists($path);

        $dataset = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $sections = collect($dataset['secciones']);

        $this->assertSame('054', $dataset['metadata']['cve_municipio']);
        $this->assertCount(402, $sections);
        $this->assertSame(402, $sections->pluck('seccion')->unique()->count());
        $this->assertSame(387, $sections->filter(fn (array $row) => $row['historial']['2024'] !== null)->count());
        $this->assertSame(342, $sections->where('elecciones_observadas', 3)->count());
        $this->assertSame(7, $sections->where('morena_gano_todas', true)->count());

        foreach ($sections as $section) {
            $this->assertSame(
                count(array_filter($section['historial'], fn ($row) => $row !== null)),
                $section['elecciones_observadas']
            );

            foreach (array_filter($section['historial'], fn ($row) => $row !== null) as $result) {
                $this->assertGreaterThanOrEqual(0, $result['participacion']);
                $this->assertLessThanOrEqual(1, $result['participacion']);
                $this->assertGreaterThanOrEqual(0, $result['porcentaje_morena']);
                $this->assertLessThanOrEqual(1, $result['porcentaje_morena']);
                $this->assertLessThanOrEqual($result['lista_nominal'], $result['votos_totales']);
                $this->assertLessThanOrEqual($result['votos_totales'], $result['votos_validos']);
            }
        }
    }
}
