<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Afiliado;
use App\Models\Lona;
use App\Models\Seccion;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocalDistrictAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $restricted;
    private User $capturer;
    private int $insideAffiliateId;
    private int $outsideAffiliateId;
    private int $insideSectionId;
    private int $outsideSectionId;
    private int $insideLonaId;
    private int $outsideLonaId;
    private int $insideActivityId;
    private int $outsideActivityId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);

        $this->restricted = User::factory()->create([
            'name' => 'Usuario restringido',
            'distrito_local' => 1,
            'must_change_password' => false,
        ]);
        $this->restricted->assignRole('Admin');
        $this->capturer = User::factory()->create(['must_change_password' => false]);

        $this->insideSectionId = DB::table('secciones')->insertGetId([
            'seccion' => '0101', 'cve_mun' => '001', 'municipio' => 'Municipio permitido',
            'distrito_local' => 1, 'distrito_federal' => 1,
        ]);
        $this->outsideSectionId = DB::table('secciones')->insertGetId([
            'seccion' => '0201', 'cve_mun' => '002', 'municipio' => 'Municipio ajeno',
            'distrito_local' => 2, 'distrito_federal' => 2,
        ]);

        $this->insideAffiliateId = DB::table('afiliados')->insertGetId($this->affiliate([
            'nombre' => 'Persona permitida', 'municipio' => 'Municipio permitido', 'cve_mun' => '001',
            'seccion' => '0101', 'distrito_local' => 1, 'distrito_federal' => 1,
        ]));
        $this->outsideAffiliateId = DB::table('afiliados')->insertGetId($this->affiliate([
            'nombre' => 'Persona ajena', 'municipio' => 'Municipio ajeno', 'cve_mun' => '002',
            'seccion' => '0201', 'distrito_local' => 2, 'distrito_federal' => 2,
        ]));

        $this->insideLonaId = DB::table('lonas')->insertGetId($this->lona([
            'seccion' => '0101', 'direccion' => 'Dirección permitida',
        ]));
        $this->outsideLonaId = DB::table('lonas')->insertGetId($this->lona([
            'seccion' => '0201', 'direccion' => 'Dirección ajena',
        ]));

        $this->insideActivityId = DB::table('actividades')->insertGetId($this->activity([
            'titulo' => 'Actividad permitida', 'distrito_local' => 1,
        ]));
        $this->outsideActivityId = DB::table('actividades')->insertGetId($this->activity([
            'titulo' => 'Actividad ajena', 'distrito_local' => 2,
        ]));
    }

    public function test_web_modules_and_direct_record_access_are_limited_to_assigned_district(): void
    {
        $this->actingAs($this->restricted);

        $dashboard = $this->get(route('dashboard'))->assertOk();
        $this->assertSame(1, $dashboard->viewData('stats')['total']);
        $this->assertSame(['Municipio permitido'], $dashboard->viewData('porMunicipio')->pluck('municipio')->all());
        $this->assertSame(['Actividad permitida'], $dashboard->viewData('actividades')->pluck('titulo')->all());

        $this->get(route('afiliados.index'))->assertOk()
            ->assertSeeText('Persona permitida')->assertDontSeeText('Persona ajena');
        $this->get(route('secciones.index'))->assertOk()
            ->assertSeeText('Municipio permitido')->assertDontSeeText('Municipio ajeno');
        $this->get(route('lonas.index'))->assertOk()
            ->assertSeeText('Dirección permitida')->assertDontSeeText('Dirección ajena');
        $this->get(route('actividades.index'))->assertOk()
            ->assertSeeText('Actividad permitida')->assertDontSeeText('Actividad ajena');

        $this->get(route('afiliados.show', $this->outsideAffiliateId))->assertNotFound();
        $this->get(route('secciones.show', $this->outsideSectionId))->assertNotFound();
        $this->get(route('lonas.show', $this->outsideLonaId))->assertNotFound();
        $this->get(route('actividades.show', $this->outsideActivityId))->assertNotFound();

        $this->get(route('mapa.data', ['estatus' => 'todos']))
            ->assertOk()->assertJsonCount(1)->assertJsonFragment(['nombre' => 'Persona permitida'])
            ->assertJsonMissing(['nombre' => 'Persona ajena']);
        $this->get(route('mapa.index'))->assertOk()
            ->assertSee('const assignedLocalDistrict = 1;', false);

        $this->get(route('reportes.secciones.data'))
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonFragment(['municipio' => 'Municipio permitido'])
            ->assertJsonMissing(['municipio' => 'Municipio ajeno']);
    }

    public function test_api_lists_and_route_binding_are_limited_to_assigned_district(): void
    {
        Sanctum::actingAs($this->restricted);

        $this->getJson('/api/v1/afiliados')->assertOk()
            ->assertJsonFragment(['nombre' => 'Persona permitida'])
            ->assertJsonMissing(['nombre' => 'Persona ajena']);
        $this->getJson('/api/v1/secciones')->assertOk()
            ->assertJsonFragment(['municipio' => 'Municipio permitido'])
            ->assertJsonMissing(['municipio' => 'Municipio ajeno']);
        $this->getJson('/api/v1/lonas')->assertOk()
            ->assertJsonFragment(['direccion' => 'Dirección permitida'])
            ->assertJsonMissing(['direccion' => 'Dirección ajena']);
        $this->getJson('/api/v1/actividades')->assertOk()
            ->assertJsonFragment(['titulo' => 'Actividad permitida'])
            ->assertJsonMissing(['titulo' => 'Actividad ajena']);
        $this->getJson('/api/v1/mapa/data?estatus=todos')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['nombre' => 'Persona ajena']);

        $this->getJson('/api/v1/afiliados/'.$this->outsideAffiliateId)->assertNotFound();
        $this->getJson('/api/v1/secciones/'.$this->outsideSectionId)->assertNotFound();
        $this->getJson('/api/v1/lonas/'.$this->outsideLonaId)->assertNotFound();
        $this->getJson('/api/v1/actividades/'.$this->outsideActivityId)->assertNotFound();
    }

    public function test_api_requires_an_explicit_affiliation_choice(): void
    {
        Sanctum::actingAs($this->restricted);

        $this->postJson('/api/v1/afiliados', [
            'nombre' => 'Persona sin afiliación',
            'seccion' => '0101',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('estatus');
    }

    public function test_restricted_creations_are_forced_to_the_assigned_district(): void
    {
        $this->actingAs($this->restricted)->post(route('actividades.store'), [
            'titulo' => 'Nueva actividad propia',
            'inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'estado' => 'programada',
        ])->assertRedirect();

        $this->assertDatabaseHas('actividades', [
            'titulo' => 'Nueva actividad propia',
            'distrito_local' => 1,
        ]);

        $this->actingAs($this->restricted)->post(route('secciones.store'), [
            'municipio' => 'Nueva sección propia',
            'cve_mun' => '003',
            'seccion' => '0301',
            'distrito_local' => 2,
            'distrito_federal' => 3,
        ])->assertRedirect();

        $this->assertDatabaseHas('secciones', [
            'seccion' => '0301',
            'distrito_local' => 1,
        ]);
    }

    private function affiliate(array $values): array
    {
        return array_merge([
            'capturista_id' => $this->capturer->id,
            'estatus' => 'validado',
            'lat' => 19.7,
            'lng' => -101.2,
            'created_at' => now(),
            'updated_at' => now(),
        ], $values);
    }

    private function lona(array $values): array
    {
        return array_merge([
            'lat' => 19.7,
            'lng' => -101.2,
            'foto_path' => 'lonas/test.jpg',
            'responsable' => 'Responsable',
            'capturado_por' => $this->capturer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ], $values);
    }

    private function activity(array $values): array
    {
        return array_merge([
            'inicio' => now()->addDay(),
            'creado_por' => $this->capturer->id,
            'estado' => 'programada',
            'created_at' => now(),
            'updated_at' => now(),
        ], $values);
    }
}
