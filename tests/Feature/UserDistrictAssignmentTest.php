<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Support\LocalDistrictAccess;
use Tests\TestCase;

class UserDistrictAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_a_local_district_when_creating_a_user(): void
    {
        $this->seed(PermissionsSeeder::class);

        DB::table('secciones')->insert([
            'seccion' => '0701',
            'cve_mun' => '070',
            'municipio' => 'Municipio de asignación',
            'distrito_local' => 7,
            'distrito_federal' => 2,
        ]);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)->post(route('settings.usuarios.store'), [
            'name' => 'Usuario distrito siete',
            'email' => 'distrito7@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'Consulta',
            'distrito_local' => 7,
        ])->assertRedirect(route('settings.usuarios.index'));

        $user = User::where('email', 'distrito7@example.test')->firstOrFail();

        $this->assertSame(7, $user->distrito_local);
        $this->assertTrue($user->hasRole('Consulta'));

        $this->actingAs($admin)
            ->get(route('settings.usuarios.edit', $user))
            ->assertOk()
            ->assertSee('value="7" selected', false);
    }

    public function test_admin_can_assign_multiple_local_districts_and_both_are_used_for_access(): void
    {
        $this->seed(PermissionsSeeder::class);

        DB::table('secciones')->insert([
            ['seccion' => '0101', 'cve_mun' => '001', 'municipio' => 'Uno', 'distrito_local' => 1, 'distrito_federal' => 1],
            ['seccion' => '0201', 'cve_mun' => '002', 'municipio' => 'Dos', 'distrito_local' => 2, 'distrito_federal' => 2],
            ['seccion' => '0301', 'cve_mun' => '003', 'municipio' => 'Tres', 'distrito_local' => 3, 'distrito_federal' => 3],
        ]);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)->post(route('settings.usuarios.store'), [
            'name' => 'Usuario dos distritos',
            'email' => 'dos-distritos@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'Admin',
            'distritos_locales' => [1, 2],
        ])->assertRedirect(route('settings.usuarios.index'));

        $user = User::where('email', 'dos-distritos@example.test')->firstOrFail();
        $this->assertSame([1, 2], $user->localDistrictNumbers());
        $this->assertSame(1, $user->distrito_local);

        $query = DB::table('secciones')->orderBy('distrito_local');
        LocalDistrictAccess::scope($query, 'distrito_local', $user);
        $this->assertSame([1, 2], $query->pluck('distrito_local')->map(fn($value) => (int) $value)->all());

        $response = $this->actingAs($user)->get(route('avance.index', ['distrito_local' => 2]))
            ->assertOk();
        $this->assertSame('2', $response->viewData('distritoLocal'));
        $this->assertSame([1, 2], $response->viewData('distritosLocales')->map(fn($value) => (int) $value)->all());
    }
}
