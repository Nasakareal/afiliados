<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.restrict_system_access' => true]);
    }

    public function test_admin_can_authenticate_through_the_api(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $user->assignRole('Admin');

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_non_admin_can_not_authenticate_through_the_api(): void
    {
        $user = User::factory()->create();
        Role::create(['name' => 'Capturista', 'guard_name' => 'web']);
        $user->assignRole('Capturista');

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
