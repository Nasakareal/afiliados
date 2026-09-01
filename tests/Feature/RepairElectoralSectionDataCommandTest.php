<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepairElectoralSectionDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_repairs_missing_catalog_and_affiliate_data_only_when_apply_is_used(): void
    {
        $user = User::factory()->create();

        DB::table('secciones')->insert([
            'seccion' => '02380',
            'cve_mun' => '106',
            'municipio' => 'Yurécuaro',
            'distrito_local' => null,
            'distrito_federal' => null,
        ]);

        $affiliateId = DB::table('afiliados')->insertGetId([
            'capturista_id' => $user->id,
            'nombre' => 'Persona de prueba',
            'municipio' => '',
            'cve_mun' => null,
            'seccion' => '2380',
            'distrito_local' => null,
            'distrito_federal' => null,
        ]);

        $source = base_path('tests/Fixtures/electoral-sections.geojson');

        $this->artisan('electoral:repair-sections', ['--source' => $source])
            ->assertExitCode(0);

        $this->assertDatabaseHas('secciones', [
            'seccion' => '02380',
            'distrito_local' => null,
            'distrito_federal' => null,
        ]);

        $this->artisan('electoral:repair-sections', [
            '--source' => $source,
            '--apply' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('secciones', [
            'seccion' => '02380',
            'distrito_local' => 1,
            'distrito_federal' => 5,
        ]);
        $this->assertDatabaseHas('afiliados', [
            'id' => $affiliateId,
            'municipio' => 'Yurécuaro',
            'cve_mun' => '106',
            'distrito_local' => 1,
            'distrito_federal' => 5,
        ]);
    }
}
