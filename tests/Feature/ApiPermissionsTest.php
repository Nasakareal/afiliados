<?php

namespace Tests\Feature;

use App\Models\Afiliado;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_read_permission_does_not_grant_write_actions(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('afiliados.ver');
        Sanctum::actingAs($user);

        $afiliado = Afiliado::create([
            'capturista_id' => $user->id,
            'nombre' => 'Prueba',
            'municipio' => 'Morelia',
        ]);

        $this->getJson('/api/v1/afiliados')->assertOk();
        $this->postJson('/api/v1/afiliados', [])->assertForbidden();
        $this->putJson("/api/v1/afiliados/{$afiliado->id}", [])->assertForbidden();
        $this->deleteJson("/api/v1/afiliados/{$afiliado->id}")->assertForbidden();
    }

    public function test_me_returns_roles_and_permissions_for_navigation(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Consulta');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonFragment(['Consulta'])
            ->assertJsonFragment(['afiliados.ver']);
    }
}
