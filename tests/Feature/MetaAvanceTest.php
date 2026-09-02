<?php

namespace Tests\Feature;

use App\Models\MetaAvance;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            ->assertSeeText('Secciones cubiertas')
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

    public function test_progress_matches_sections_by_municipality_and_section(): void
    {
        DB::table('secciones')->insert([
            'seccion' => '0001',
            'cve_mun' => '002',
            'municipio' => 'Otro municipio',
            'distrito_local' => 2,
            'distrito_federal' => 4,
        ]);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');
        $capturer = User::factory()->create(['must_change_password' => false]);

        DB::table('afiliados')->insert([
            'capturista_id' => $capturer->id,
            'nombre' => 'Persona de municipio uno',
            'municipio' => 'Municipio de prueba',
            'cve_mun' => '001',
            'seccion' => '0001',
            'distrito_local' => 1,
            'distrito_federal' => 3,
            'perfil' => 'Gladyz Butanda',
            'estatus' => 'validado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('avance.index'))
            ->assertOk();

        $rows = $response->viewData('avance')->keyBy(
            fn(array $row) => $row['cve_mun'].'|'.$row['distrito_local']
        );

        $this->assertSame(1, $rows['001|1']['total_convencidos']);
        $this->assertSame(0, $rows['002|2']['total_convencidos']);
        $this->assertSame(['Gladyz Butanda'], $response->viewData('referentes')->all());
    }

    public function test_dashboard_and_progress_use_the_same_active_convinced_records(): void
    {
        DB::table('secciones')->insert([
            'seccion' => '0002',
            'cve_mun' => '001',
            'municipio' => 'Municipio de prueba',
            'distrito_local' => 1,
            'distrito_federal' => 3,
        ]);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');

        foreach (['validado', 'descartado'] as $index => $estatus) {
            DB::table('afiliados')->insert([
                'capturista_id' => $admin->id,
                'nombre' => 'Persona '.($index + 1),
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0001',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'perfil' => 'Gladyz Butanda',
                'estatus' => $estatus,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $dashboard = $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $avance = $this->actingAs($admin)->get(route('avance.index'))->assertOk();

        $this->assertSame(2, $dashboard->viewData('stats')['total']);
        $this->assertSame(2, $avance->viewData('totales')['secciones']);
        $this->assertSame(1, $avance->viewData('totales')['secciones_cubiertas']);
        $this->assertSame(50.0, $avance->viewData('totales')['porcentaje_secciones_cubiertas']);
        $this->assertSame(
            $dashboard->viewData('stats')['total'],
            $avance->viewData('totales')['total_convencidos']
        );
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

    public function test_referent_filter_only_lists_official_names_with_active_records(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');
        $capturer = User::factory()->create(['must_change_password' => false]);

        DB::table('afiliados')->insert([
            [
                'capturista_id' => $capturer->id,
                'nombre' => 'Perfil válido',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0001',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'perfil' => 'Moises Navarro',
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'capturista_id' => $capturer->id,
                'nombre' => 'Perfil histórico inválido',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0001',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'perfil' => 'A',
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('lonas')->insert([
            'seccion' => '0001',
            'direccion' => 'Dirección de prueba',
            'lat' => 19.7,
            'lng' => -101.2,
            'foto_path' => 'lonas/prueba.jpg',
            'responsable' => '0806',
            'capturado_por' => $capturer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('avance.index', [
            'referente' => '0806',
        ]))->assertOk();

        $this->assertSame('', $response->viewData('referente'));
        $this->assertSame(['Moises Navarro'], $response->viewData('referentes')->all());
        $this->assertSame(['Moises Navarro'], $response->viewData('topReferentes')->pluck('name')->all());
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
                'perfil' => 'Moises Navarro',
                'estatus' => 'validado',
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
                'perfil' => 'Moises Navarro',
                'estatus' => 'validado',
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
                'perfil' => 'Andrea Serna',
                'estatus' => 'validado',
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
                'responsable' => 'Moises Navarro',
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
                'responsable' => 'Andrea Serna',
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
        $this->assertSame('Moises Navarro', $response->viewData('topReferentes')->first()->name);
    }

    public function test_user_assigned_to_a_local_district_cannot_view_or_modify_another_one(): void
    {
        DB::table('secciones')->insert([
            'seccion' => '0002',
            'cve_mun' => '002',
            'municipio' => 'Municipio fuera del alcance',
            'distrito_local' => 2,
            'distrito_federal' => 4,
        ]);

        $restricted = User::factory()->create([
            'must_change_password' => false,
            'distrito_local' => 1,
        ]);
        $restricted->assignRole('Admin');

        $insideCapturer = User::factory()->create([
            'name' => 'Capturista distrito permitido',
            'must_change_password' => false,
        ]);
        $outsideCapturer = User::factory()->create([
            'name' => 'Capturista distrito ajeno',
            'must_change_password' => false,
        ]);

        DB::table('afiliados')->insert([
            [
                'capturista_id' => $insideCapturer->id,
                'nombre' => 'Persona permitida',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0001',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'perfil' => 'Moises Navarro',
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'capturista_id' => $outsideCapturer->id,
                'nombre' => 'Persona fuera del alcance',
                'municipio' => 'Municipio fuera del alcance',
                'cve_mun' => '002',
                'seccion' => '0002',
                'distrito_local' => 2,
                'distrito_federal' => 4,
                'perfil' => 'Andrea Serna',
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($restricted)->get(route('avance.index', [
            'distrito_local' => 2,
        ]));

        $response->assertOk()
            ->assertSeeText('Distrito local 01')
            ->assertDontSeeText('Todos los locales');

        $this->assertSame('1', $response->viewData('distritoLocal'));
        $this->assertTrue($response->viewData('distritoLocalRestringido'));
        $this->assertSame([1], $response->viewData('distritosLocales')->map(fn($value) => (int)$value)->all());
        $this->assertSame([1], $response->viewData('avance')->pluck('distrito_local')->unique()->values()->all());
        $this->assertSame(1, $response->viewData('totales')['total_convencidos']);
        $this->assertSame(['Moises Navarro'], $response->viewData('referentes')->values()->all());
        $this->assertSame(['Capturista distrito permitido'], $response->viewData('capturistas')->pluck('name')->all());

        $this->actingAs($restricted)->post(route('avance.metas.store'), [
            'cve_mun' => '002',
            'distrito_local' => 2,
            'meta_convencidos' => 10,
            'meta_lonas' => 1,
        ])->assertForbidden();

        $this->assertDatabaseMissing('meta_avances', [
            'cve_mun' => '002',
            'distrito_local' => 2,
        ]);
    }

    public function test_each_municipality_has_a_green_section_vote_breakdown(): void
    {
        DB::table('secciones')->insert([
            'seccion' => '0002',
            'cve_mun' => '001',
            'municipio' => 'Municipio de prueba',
            'distrito_local' => 1,
            'distrito_federal' => 3,
        ]);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');
        $capturer = User::factory()->create(['must_change_password' => false]);

        DB::table('afiliados')->insert([
            [
                'capturista_id' => $capturer->id,
                'nombre' => 'Voto sección uno A',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0001',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'perfil' => 'Moises Navarro',
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'capturista_id' => $capturer->id,
                'nombre' => 'Voto sección uno B',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '1',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'perfil' => 'Andrea Serna',
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('avance.index'))
            ->assertOk()
            ->assertSee('btn btn-success btn-secciones-municipio', false)
            ->assertSee('data-scope="001|1"', false)
            ->assertSee('id="modalSeccionesMunicipio"', false)
            ->assertSeeText('Votos / convencidos');

        $detalle = $response->viewData('seccionesPorMunicipio')->get('001|1');

        $this->assertCount(2, $detalle);
        $this->assertSame(2, $detalle->firstWhere('seccion', '0001')['total']);
        $this->assertSame(0, $detalle->firstWhere('seccion', '0002')['total']);
    }

    public function test_filtered_progress_can_be_downloaded_with_people_and_districts(): void
    {
        DB::table('secciones')->insert([
            'seccion' => '0002',
            'cve_mun' => '002',
            'municipio' => 'Municipio excluido',
            'distrito_local' => 2,
            'distrito_federal' => 4,
        ]);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');
        $capturer = User::factory()->create(['name' => 'Capturista Excel', 'must_change_password' => false]);

        DB::table('afiliados')->insert([
            [
                'capturista_id' => $capturer->id,
                'nombre' => 'Persona incluida',
                'apellido_paterno' => 'Distrito Uno',
                'telefono' => '4430000001',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0001',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'perfil' => 'Moises Navarro',
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'capturista_id' => $capturer->id,
                'nombre' => 'Persona excluida',
                'apellido_paterno' => null,
                'telefono' => null,
                'municipio' => 'Municipio excluido',
                'cve_mun' => '002',
                'seccion' => '0002',
                'distrito_local' => 2,
                'distrito_federal' => 4,
                'perfil' => 'Andrea Serna',
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $query = [
            'distrito_local' => 1,
            'referente' => 'Moises Navarro',
        ];

        $page = $this->actingAs($admin)->get(route('avance.index', $query));
        $page->assertOk()
            ->assertSeeText('Ver personas y distritos')
            ->assertSeeText('Descargar Excel');

        $detail = $this->actingAs($admin)->get(route('avance.convencidos', $query));
        $detail->assertOk()
            ->assertSeeText('Persona incluida Distrito Uno')
            ->assertSeeText('DL 01')
            ->assertSeeText('DFn 03')
            ->assertDontSeeText('Persona excluida');

        $response = $this->actingAs($admin)->get(route('avance.export.xlsx', $query));
        $response->assertOk()->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $path = $response->baseResponse->getFile()->getPathname();
        $workbook = IOFactory::load($path);

        $this->assertSame(['Avance', 'Personas convencidas'], $workbook->getSheetNames());
        $people = $workbook->getSheetByName('Personas convencidas');
        $this->assertSame('Persona incluida Distrito Uno', $people->getCell('B6')->getValue());
        $this->assertSame(1, $people->getCell('I6')->getValue());
        $this->assertSame(3, $people->getCell('J6')->getValue());
        $this->assertSame('DL 01 / DFn 03', $people->getCell('M6')->getValue());
        $this->assertNull($people->getCell('B7')->getValue());
        $this->assertStringContainsString('Referente: Moises Navarro', $people->getCell('A2')->getValue());

        $workbook->disconnectWorksheets();
    }

    public function test_restricted_user_export_cannot_include_another_local_district(): void
    {
        DB::table('secciones')->insert([
            'seccion' => '0002',
            'cve_mun' => '002',
            'municipio' => 'Distrito ajeno',
            'distrito_local' => 2,
            'distrito_federal' => 4,
        ]);

        $restricted = User::factory()->create([
            'must_change_password' => false,
            'distrito_local' => 1,
        ]);
        $restricted->assignRole('Admin');

        DB::table('afiliados')->insert([
            [
                'capturista_id' => $restricted->id,
                'nombre' => 'Persona permitida en Excel',
                'municipio' => 'Municipio de prueba',
                'cve_mun' => '001',
                'seccion' => '0001',
                'distrito_local' => 1,
                'distrito_federal' => 3,
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'capturista_id' => $restricted->id,
                'nombre' => 'Persona prohibida en Excel',
                'municipio' => 'Distrito ajeno',
                'cve_mun' => '002',
                'seccion' => '0002',
                'distrito_local' => 2,
                'distrito_federal' => 4,
                'estatus' => 'validado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($restricted)->get(route('avance.export.xlsx', [
            'distrito_local' => 2,
        ]));
        $response->assertOk();

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $people = $workbook->getSheetByName('Personas convencidas');

        $this->assertSame('Persona permitida en Excel', $people->getCell('B6')->getValue());
        $this->assertNull($people->getCell('B7')->getValue());
        $this->assertStringContainsString('Distrito local: 01', $people->getCell('A2')->getValue());

        $workbook->disconnectWorksheets();
    }

    public function test_official_goals_match_the_delivered_workbook_totals(): void
    {
        $metas = require database_path('data/official_meta_avances.php');

        $this->assertCount(117, $metas);
        $this->assertSame(326400, array_sum(array_column($metas, 'meta')));
        $this->assertCount(117, collect($metas)->unique(fn($meta) => $meta['cve_mun'].'|'.$meta['distrito_local']));
        $this->assertSame(117, DB::table('meta_avances')->where('tipo', MetaAvance::TIPO_CONVENCIDOS)->count());
        $this->assertSame(326400, (int)DB::table('meta_avances')->sum('meta'));
        $this->assertSame(0, DB::table('meta_avances')->where('tipo', MetaAvance::TIPO_LONAS)->count());

        $morelia = collect($metas)->where('municipio', 'Morelia')->sortBy('distrito_local')->values();

        $this->assertSame([10, 11, 16, 17], $morelia->pluck('distrito_local')->all());
        $this->assertSame([11880, 12960, 8400, 12000], $morelia->pluck('meta')->all());
    }
}
