<?php

namespace Tests\Feature;

use App\Console\Commands\LoadTablaAparteDemo;
use App\Support\DemoTablaAparte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class LoadTablaAparteDemoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_assigns_exactly_ten_percent_as_non_affiliates(): void
    {
        $statuses = collect(range(2, 101))
            ->map(fn($row) => DemoTablaAparte::statusForSourceRow((string) $row));

        $this->assertSame(90, $statuses->filter(fn($status) => $status === 'validado')->count());
        $this->assertSame(10, $statuses->filter(fn($status) => $status === 'descartado')->count());
    }

    public function test_it_resolves_the_same_section_number_by_municipality(): void
    {
        DB::table('secciones')->insert([
            [
                'cve_mun' => '001',
                'municipio' => 'Municipio uno',
                'seccion' => '1706',
                'distrito_federal' => 1,
                'distrito_local' => 2,
            ],
            [
                'cve_mun' => '002',
                'municipio' => 'Municipio dos',
                'seccion' => '1706',
                'distrito_federal' => 3,
                'distrito_local' => 4,
            ],
        ]);

        $expected = [
            '001|1706' => ['cve_mun' => '001', 'seccion' => '1706'],
            '002|1706' => ['cve_mun' => '002', 'seccion' => '1706'],
        ];

        $method = new ReflectionMethod(LoadTablaAparteDemo::class, 'sectionCatalog');
        $method->setAccessible(true);
        $catalog = $method->invoke(app(LoadTablaAparteDemo::class), $expected);

        $this->assertSame('Municipio uno', $catalog['001|1706']['municipio']);
        $this->assertSame('Municipio dos', $catalog['002|1706']['municipio']);
        $this->assertSame(4, $catalog['002|1706']['distrito_local']);
    }
}
