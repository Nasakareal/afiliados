<?php

namespace Tests\Unit;

use App\Services\SocioeconomicSectionLayer;
use Tests\TestCase;

class SocioeconomicSectionLayerTest extends TestCase
{
    public function test_it_attaches_aggregate_context_and_preserves_missing_coverage(): void
    {
        $dataset = json_decode(
            file_get_contents(database_path('data/electoral/morelia_municipal_sections.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $result = app(SocioeconomicSectionLayer::class)->enrich($dataset);
        $sections = collect($result['secciones']);
        $covered = $sections->filter(fn (array $section): bool => $section['socioeconomico'] !== null);

        $this->assertSame(373, $result['socioeconomico']['secciones_con_datos']);
        $this->assertSame(29, $result['socioeconomico']['secciones_sin_datos']);
        $this->assertSame(317, $result['socioeconomico']['cobertura_alta']);
        $this->assertSame(373, $covered->count());

        $section = $sections->firstWhere('seccion', 941)['socioeconomico'];
        $this->assertSame('Bajo', $section['grado_rezago']);
        $this->assertSame(1.0, $section['cobertura_territorial']);
        $this->assertArrayHasKey('sin_internet', $section['indicadores']);

        foreach ($covered as $row) {
            $context = $row['socioeconomico'];
            $this->assertGreaterThanOrEqual(.01, $context['cobertura_territorial']);
            $this->assertLessThanOrEqual(1, $context['cobertura_territorial']);
            $this->assertGreaterThanOrEqual(10, $context['poblacion_representada']);
        }
    }
}
