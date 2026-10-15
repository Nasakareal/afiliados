<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemAccessRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.restrict_system_access' => true]);
        $this->seed(PermissionsSeeder::class);
    }

    public function test_existing_non_admin_web_session_is_closed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Capturista');

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => User::ACCESS_RESTRICTION_MESSAGE,
        ]);
    }

    public function test_existing_non_admin_api_token_is_denied(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Capturista');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath('message', User::ACCESS_RESTRICTION_MESSAGE);
    }

    public function test_admin_with_an_existing_session_keeps_access(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
