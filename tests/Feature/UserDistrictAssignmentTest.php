<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
}
