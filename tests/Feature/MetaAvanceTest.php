<?php

namespace Tests\Feature;

use App\Models\MetaAvance;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MetaAvanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);

        DB::table('secciones')->insert([
            'seccion' => '0001',
            'cve_mun' => '001',
            'municipio' => 'Municipio de prueba',
            'distrito_local' => '1',
            'distrito_federal' => '3',
        ]);
    }

    public function test_admin_can_see_convinced_and_banner_progress_with_both_district_filters(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)
            ->get(route('avance.index'))
            ->assertOk()
            ->assertSeeText('Michoacán')
            ->assertSeeText('Todos los distritos')
            ->assertSeeText('Todos los locales')
            ->assertSee('id="quickDistritoLocal"', false)
            ->assertSee('onchange="this.form.submit()"', false)
            ->assertSeeText('Histórico completo')
            ->assertDontSee('name="fecha_inicio"', false)
            ->assertDontSee('name="fecha_fin"', false)
            ->assertSee('avanceDistrictMap')
            ->assertSee('toggleAvanceMunicipalityLabels')
            ->assertSeeText('Nombres de municipios')
            ->assertSeeInOrder(['Meta<br>convencidos', 'Total<br>convencidos'], false)
            ->assertSeeInOrder(['Meta<br>lonas', 'Total<br>lonas'], false)
            ->assertSeeText('Top capturistas por convencidos')
            ->assertSeeText('Referentes por convencidos')
            ->assertSee('Asignar meta');

        $this->actingAs($admin)
            ->get(route('avance.index', ['distrito_federal' => '3']))
            ->assertOk()
            ->assertSeeText('Distrito 03 Zitácuaro');
    }

    public function test_admin_saves_convinced_and_banner_goals_without_date_filters(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)->post(route('avance.metas.store'), [
            'cve_mun' => '001',
            'distrito_local' => 1,
            'meta_convencidos' => 5,
            'meta_lonas' => 2,
        ])->assertRedirect(route('avance.index'));

        $this->assertDatabaseHas('meta_avances', [
            'tipo' => MetaAvance::TIPO_CONVENCIDOS,
            'cve_mun' => '001',
            'distrito_local' => 1,
            'meta' => 5,
        ]);
        $this->assertDatabaseHas('meta_avances', [
            'tipo' => MetaAvance::TIPO_LONAS,
            'cve_mun' => '001',
            'distrito_local' => 1,
            'meta' => 2,
        ]);
    }

    public function test_coordinator_can_view_but_cannot_change_goals(): void
    {
        $coordinator = User::factory()->create(['must_change_password' => false]);
        $coordinator->assignRole('Coordinador');

        $this->actingAs($coordinator)->get(route('avance.index'))->assertOk();
        $this->actingAs($coordinator)->post(route('avance.metas.store'), [
            'cve_mun' => '001',
            'distrito_local' => 1,
            'meta_convencidos' => 5,
            'meta_lonas' => 2,
        ])->assertForbidden();
    }

    public function test_convinced_goal_can_be_saved_without_inventing_a_banner_goal(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)->post(route('avance.metas.store'), [
            'cve_mun' => '001',
            'distrito_local' => 1,
            'meta_convencidos' => 180,
            'meta_lonas' => '',
        ])->assertRedirect(route('avance.index'));

        $this->assertDatabaseHas('meta_avances', [
            'tipo' => MetaAvance::TIPO_CONVENCIDOS,
            'cve_mun' => '001',
            'distrito_local' => 1,
            'meta' => 180,
        ]);
        $this->assertDatabaseMissing('meta_avances', [
            'tipo' => MetaAvance::TIPO_LONAS,
            'cve_mun' => '001',
            'distrito_local' => 1,
        ]);
    }

    public function test_local_district_filters_historical_totals_lonas_and_rankings(): void
    {
        DB::table('secciones')->insert([
            'seccion' => '0002',
            'cve_mun' => '001',
            'municipio' => 'Municipio de prueba',
            'distrito_local' => '2',
            'distrito_federal' => '3',
        ]);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');
        $leader = User::factory()->create(['name' => 'Capturista líder', 'must_change_password' => false]);
        $other = User::factory()->create(['name' => 'Otro capturista', 'must_change_password' => false]);

        DB::table('afiliados')->insert([
            [
                'capturista_id' => $leader->id,
                'nombre' => 'Persona uno',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0001',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'perfil' => 'Referente líder',
                'estatus' => 'pendiente',
                'fecha_convencimiento' => '2025-01-10 10:00:00',
                'created_at' => '2025-01-10 10:00:00',
                'updated_at' => '2025-01-10 10:00:00',
            ],
            [
                'capturista_id' => $leader->id,
                'nombre' => 'Persona dos',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0001',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'perfil' => 'Referente líder',
                'estatus' => 'pendiente',
                'fecha_convencimiento' => '2026-08-11 10:00:00',
                'created_at' => '2026-08-11 10:00:00',
                'updated_at' => '2026-08-11 10:00:00',
            ],
            [
                'capturista_id' => $other->id,
                'nombre' => 'Fuera del distrito local',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0002',
                'distrito_local' => 2,
                'distrito_federal' => 3,
                'perfil' => 'Otro referente',
                'estatus' => 'pendiente',
                'fecha_convencimiento' => '2026-08-12 10:00:00',
                'created_at' => '2026-08-12 10:00:00',
                'updated_at' => '2026-08-12 10:00:00',
            ],
        ]);

        DB::table('lonas')->insert([
            [
                'seccion' => '0001',
                'direccion' => 'Dirección uno',
                'lat' => 19.7,
                'lng' => -101.2,
                'foto_path' => 'lonas/uno.jpg',
                'responsable' => 'Referente líder',
                'capturado_por' => $leader->id,
                'created_at' => '2025-01-10 10:00:00',
                'updated_at' => '2025-01-10 10:00:00',
            ],
            [
                'seccion' => '0002',
                'direccion' => 'Dirección dos',
                'lat' => 19.8,
                'lng' => -101.3,
                'foto_path' => 'lonas/dos.jpg',
                'responsable' => 'Otro referente',
                'capturado_por' => $other->id,
                'created_at' => '2026-08-12 10:00:00',
                'updated_at' => '2026-08-12 10:00:00',
            ],
        ]);

        DB::table('meta_avances')->insert([
            [
                'tipo' => MetaAvance::TIPO_CONVENCIDOS,
                'cve_mun' => '001',
                'distrito_local' => 1,
                'meta' => 100,
                'fecha_inicio' => '2000-01-01',
                'fecha_fin' => '2099-12-31',
                'activa' => true,
            ],
            [
                'tipo' => MetaAvance::TIPO_CONVENCIDOS,
                'cve_mun' => '001',
                'distrito_local' => 2,
                'meta' => 200,
                'fecha_inicio' => '2000-01-01',
                'fecha_fin' => '2099-12-31',
                'activa' => true,
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('avance.index', [
            'distrito_local' => 1,
        ]));

        $response->assertOk()->assertSeeText('Distrito local 01');
        $this->assertSame(2, $response->viewData('totales')['total_convencidos']);
        $this->assertSame(100, $response->viewData('totales')['meta_convencidos']);
        $this->assertSame(1, $response->viewData('totales')['total_lonas']);
        $this->assertSame(2, $response->viewData('convencidosPorSeccion')->get('1'));
        $this->assertSame('Capturista líder', $response->viewData('topCapturistas')->first()->name);
        $this->assertSame(2, (int)$response->viewData('topCapturistas')->first()->total);
        $this->assertSame('Referente líder', $response->viewData('topReferentes')->first()->name);
    }

    public function test_official_goals_match_the_delivered_workbook_totals(): void
    {
        $metas = require database_path('data/official_meta_avances.php');

        $this->assertCount(117, $metas);
        $this->assertSame(489600, array_sum(array_column($metas, 'meta')));
        $this->assertCount(117, collect($metas)->unique(fn($meta) => $meta['cve_mun'].'|'.$meta['distrito_local']));
        $this->assertSame(117, DB::table('meta_avances')->where('tipo', MetaAvance::TIPO_CONVENCIDOS)->count());
        $this->assertSame(489600, (int)DB::table('meta_avances')->sum('meta'));
        $this->assertSame(0, DB::table('meta_avances')->where('tipo', MetaAvance::TIPO_LONAS)->count());

        $morelia = collect($metas)->where('municipio', 'Morelia')->sortBy('distrito_local')->values();

        $this->assertSame([10, 11, 16, 17], $morelia->pluck('distrito_local')->all());
        $this->assertSame([17820, 19440, 12600, 18000], $morelia->pluck('meta')->all());
    }
}
