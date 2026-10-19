<?php

namespace Tests\Unit;

use App\Services\ElectoralOfferDemandIndex;
use Tests\TestCase;

class ElectoralOfferDemandIndexTest extends TestCase
{
    public function test_it_calculates_only_comparable_sections_without_turning_missing_data_into_zero(): void
    {
        $dataset = json_decode(
            file_get_contents(database_path('data/electoral/morelia_municipal_sections.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $result = app(ElectoralOfferDemandIndex::class)->enrich($dataset);
        $sections = collect($result['secciones']);
        $indexes = $sections->pluck('indice_electoral')->filter();

        $this->assertSame(365, $result['indice_electoral']['secciones_calculadas']);
        $this->assertSame(37, $result['indice_electoral']['secciones_sin_indice']);
        $this->assertNull($sections->firstWhere('seccion', 2802)['indice_electoral']);
        $this->assertNull($sections->firstWhere('seccion', 2747)['indice_electoral']);

        foreach ($indexes as $index) {
            $this->assertGreaterThanOrEqual(0, $index['ioe']);
            $this->assertLessThanOrEqual(.50, $index['ioe']);
            $this->assertGreaterThanOrEqual(0, $index['ide']);
            $this->assertLessThanOrEqual(.50, $index['ide']);
            if ($index['iode'] !== null) {
                $this->assertGreaterThanOrEqual(0, $index['iode']);
            }
        }
    }
}
