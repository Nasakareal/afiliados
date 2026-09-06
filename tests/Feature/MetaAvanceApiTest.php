<?php

namespace Tests\Feature;

use App\Models\MetaAvance;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MetaAvanceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);

        DB::table('secciones')->insert([
            'seccion' => '0001',
            'cve_mun' => '001',
            'municipio' => 'Municipio móvil',
            'distrito_local' => 1,
            'distrito_federal' => 3,
        ]);
    }

    public function test_mobile_api_exposes_progress_and_allows_authorized_goal_updates(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/avance')
            ->assertOk()
            ->assertJsonPath('avance.0.municipio', 'Municipio móvil')
            ->assertJsonPath('totales.total_convencidos', 0)
            ->assertJsonStructure([
                'avance',
                'totales',
                'capturistas',
                'referentes',
                'topCapturistas',
                'topReferentes',
                'seccionesPorMunicipio',
            ]);

        $this->postJson('/api/v1/avance/metas', [
            'cve_mun' => '001',
            'distrito_local' => 1,
            'meta_convencidos' => 180,
            'meta_lonas' => 12,
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('meta_avances', [
            'tipo' => MetaAvance::TIPO_CONVENCIDOS,
            'cve_mun' => '001',
            'distrito_local' => 1,
            'meta' => 180,
        ]);
    }

    public function test_mobile_progress_api_respects_permissions(): void
    {
        Sanctum::actingAs(User::factory()->create(['must_change_password' => false]));

        $this->getJson('/api/v1/avance')->assertForbidden();
        $this->postJson('/api/v1/avance/metas', [])->assertForbidden();
    }
}
