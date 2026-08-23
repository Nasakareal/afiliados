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

    public function test_admin_can_see_the_national_and_state_progress_table(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)
            ->get(route('avance.index'))
            ->assertOk()
            ->assertSeeText('Distrito 03 Zitácuaro')
            ->assertSee('avanceDistrictMap')
            ->assertSeeInOrder(['Meta<br>nacional', 'Avance<br>nacional'], false)
            ->assertSeeInOrder(['Meta<br>estatal', 'Avance<br>estatal'], false)
            ->assertSee('Asignar meta');
    }

    public function test_admin_saves_both_goals_for_the_same_period(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)->post(route('avance.metas.store'), [
            'cve_mun' => '001',
            'meta_nacional' => 2,
            'meta_estatal' => 5,
            'fecha_inicio' => '2026-08-01',
            'fecha_fin' => '2026-08-31',
        ])->assertRedirect(route('avance.index', [
            'fecha_inicio' => '2026-08-01',
            'fecha_fin' => '2026-08-31',
        ]));

        $this->assertDatabaseHas('meta_avances', [
            'tipo' => MetaAvance::TIPO_NACIONAL,
            'cve_mun' => '001',
            'meta' => 2,
        ]);
        $this->assertDatabaseHas('meta_avances', [
            'tipo' => MetaAvance::TIPO_ESTATAL,
            'cve_mun' => '001',
            'meta' => 5,
        ]);
    }

    public function test_coordinator_can_view_but_cannot_change_goals(): void
    {
        $coordinator = User::factory()->create(['must_change_password' => false]);
        $coordinator->assignRole('Coordinador');

        $this->actingAs($coordinator)->get(route('avance.index'))->assertOk();
        $this->actingAs($coordinator)->post(route('avance.metas.store'), [
            'cve_mun' => '001',
            'meta_nacional' => 2,
            'meta_estatal' => 5,
            'fecha_inicio' => '2026-08-01',
            'fecha_fin' => '2026-08-31',
        ])->assertForbidden();
    }
}
