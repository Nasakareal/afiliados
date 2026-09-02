<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AfiliadoSectionLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);

        DB::table('secciones')->insert([
            [
                'seccion' => '0405',
                'cve_mun' => '027',
                'municipio' => 'Chucándiro',
                'distrito_local' => 2,
                'distrito_federal' => 7,
            ],
            [
                'seccion' => '0101',
                'cve_mun' => '001',
                'municipio' => 'Municipio fuera de alcance',
                'distrito_local' => 1,
                'distrito_federal' => 8,
            ],
        ]);
    }

    public function test_capturer_can_resolve_an_assigned_section_without_section_catalog_permission(): void
    {
        $capturer = User::factory()->create([
            'must_change_password' => false,
            'distrito_local' => 2,
        ]);
        $capturer->assignRole('Capturista');

        $this->assertTrue($capturer->can('afiliados.crear'));
        $this->assertFalse($capturer->can('secciones.ver'));

        $this->actingAs($capturer)
            ->getJson(route('secciones.lookup', ['seccion' => '0405']))
            ->assertOk()
            ->assertJson([
                'seccion' => '0405',
                'municipio' => 'Chucándiro',
                'cve_mun' => '027',
                'distrito_local' => 2,
                'distrito_federal' => 7,
            ]);

        $this->actingAs($capturer)
            ->getJson(route('secciones.lookup', ['seccion' => '0101']))
            ->assertNotFound();
    }

    public function test_capturer_registration_uses_catalog_municipality_and_district_automatically(): void
    {
        $capturer = User::factory()->create([
            'must_change_password' => false,
            'distrito_local' => 2,
        ]);
        $capturer->assignRole('Capturista');

        $this->actingAs($capturer)
            ->post(route('afiliados.store'), [
                'nombre' => 'Persona de prueba',
                'seccion' => '0405',
                'perfil' => 'Andrea Serna',
                'estatus' => 'validado',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('afiliados', [
            'capturista_id' => $capturer->id,
            'nombre' => 'PERSONA DE PRUEBA',
            'seccion' => '0405',
            'municipio' => 'Chucándiro',
            'cve_mun' => '027',
            'distrito_local' => 2,
            'distrito_federal' => 7,
        ]);
    }

    public function test_registration_requires_an_explicit_affiliation_choice(): void
    {
        $capturer = User::factory()->create([
            'must_change_password' => false,
            'distrito_local' => 2,
        ]);
        $capturer->assignRole('Capturista');

        $this->actingAs($capturer)
            ->get(route('registro'))
            ->assertOk()
            ->assertSeeText('Selecciona Sí o No')
            ->assertSee('name="estatus"', false)
            ->assertSee('required', false);

        $this->actingAs($capturer)
            ->from(route('registro'))
            ->post(route('afiliados.store'), [
                'nombre' => 'Persona sin afiliación',
                'seccion' => '0405',
                'perfil' => 'Andrea Serna',
            ])
            ->assertRedirect(route('registro'))
            ->assertSessionHasErrors('estatus');

        $this->assertDatabaseMissing('afiliados', [
            'nombre' => 'PERSONA SIN AFILIACION',
        ]);
    }
}
