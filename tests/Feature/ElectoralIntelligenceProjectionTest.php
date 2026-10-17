<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ElectoralIntelligenceProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_projection_uses_only_yes_affiliates_as_mobilizable_base(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Admin');

        $this->insertAffiliate($user->id, '1118', 'validado');
        $this->insertAffiliate($user->id, '1118', 'validado');
        $this->insertAffiliate($user->id, '1118', 'descartado');
        $this->insertAffiliate($user->id, '1200', 'validado');
        $this->insertAffiliate($user->id, '9999', 'validado', 'Morelos', '054');
        $this->insertAffiliate($user->id, '9998', 'validado');

        $response = $this->actingAs($user)
            ->get(route('electoral.data'))
            ->assertOk()
            ->assertJsonPath('operacion.registrados', 4)
            ->assertJsonPath('operacion.afiliados_si', 3)
            ->assertJsonPath('operacion.afiliados_no', 1)
            ->assertJsonPath('operacion.secciones_cubiertas', 2)
            ->assertJsonPath('operacion.registros_fuera_mapa', 1)
            ->assertJsonPath('operacion.sin_base_si', 400)
            ->assertJsonPath('operacion.estructura_minima', 2)
            ->assertHeader('Cache-Control', 'no-store, private');

        $section = collect($response->json('secciones'))->firstWhere('seccion', 1118);

        $this->assertSame(3, $section['operacion']['registrados']);
        $this->assertSame(2, $section['operacion']['afiliados_si']);
        $this->assertSame(1, $section['operacion']['afiliados_no']);

        $this->get(route('electoral.index'))
            ->assertOk()
            ->assertSeeText('Proyección operativa con la base actual')
            ->assertSeeText('Podrían acudir')
            ->assertSeeText('Cauteloso · 60%')
            ->assertSeeText('Dónde fortalecer la estructura')
            ->assertSeeText('Prioridad sugerida')
            ->assertSee('Sí afiliados registrados por sección');
    }

    private function insertAffiliate(
        int $capturistaId,
        string $section,
        string $status,
        string $municipality = 'Morelia',
        string $municipalityKey = '053'
    ): void
    {
        DB::table('afiliados')->insert([
            'capturista_id' => $capturistaId,
            'nombre' => 'Registro de prueba',
            'municipio' => $municipality,
            'cve_mun' => $municipalityKey,
            'seccion' => $section,
            'distrito_local' => 17,
            'distrito_federal' => 10,
            'estatus' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
