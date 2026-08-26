<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');
        Sanctum::actingAs($admin);
    }

    public function test_mobile_admin_can_manage_users_roles_permissions_and_settings(): void
    {
        DB::table('secciones')->insert([
            'seccion' => '0901',
            'cve_mun' => '090',
            'municipio' => 'Municipio móvil',
            'distrito_local' => 9,
            'distrito_federal' => 1,
        ]);

        $created = $this->postJson('/api/v1/admin/usuarios', [
            'name' => 'Usuario móvil',
            'email' => 'movil@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'Consulta',
            'distrito_local' => 9,
        ])->assertCreated()
            ->assertJsonPath('role', 'Consulta')
            ->assertJsonPath('distrito_local', 9);

        $userId = $created->json('id');
        $this->getJson('/api/v1/admin/usuarios')
            ->assertOk()
            ->assertJsonFragment(['email' => 'movil@example.test']);
        $this->putJson("/api/v1/admin/usuarios/{$userId}", [
            'name' => 'Usuario actualizado',
            'email' => 'movil@example.test',
            'role' => 'Capturista',
        ])->assertOk()
            ->assertJsonPath('role', 'Capturista')
            ->assertJsonPath('distrito_local', 9);

        $role = $this->postJson('/api/v1/admin/roles', ['name' => 'Móvil'])
            ->assertCreated();
        $roleId = $role->json('id');
        $this->putJson("/api/v1/admin/roles/{$roleId}/permisos", [
            'permissions' => ['afiliados.ver'],
        ])->assertOk()->assertJsonFragment(['afiliados.ver']);

        $this->putJson('/api/v1/admin/app', [
            'captura_habilitada' => false,
            'motivo_bloqueo' => 'Mantenimiento',
        ])->assertOk()->assertJsonPath('captura_habilitada', false);
    }

    public function test_mobile_admin_can_manage_comunicados(): void
    {
        $created = $this->postJson('/api/v1/admin/comunicados', [
            'titulo' => 'Aviso móvil',
            'contenido' => 'Contenido del aviso',
            'estado' => 'borrador',
        ])->assertCreated();
        $id = $created->json('id');

        $this->getJson('/api/v1/admin/comunicados')
            ->assertOk()->assertJsonFragment(['titulo' => 'Aviso móvil']);
        $this->putJson("/api/v1/admin/comunicados/{$id}", [
            'titulo' => 'Aviso publicado',
            'contenido' => 'Contenido del aviso',
            'estado' => 'publicado',
        ])->assertOk()->assertJsonPath('estado', 'publicado');
        $this->deleteJson("/api/v1/admin/comunicados/{$id}")->assertOk();
    }
}
