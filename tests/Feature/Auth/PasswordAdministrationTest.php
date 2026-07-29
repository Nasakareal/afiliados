<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_public_password_recovery_routes_do_not_exist(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', [])->assertNotFound();
        $this->get('/reset-password/token')->assertNotFound();
        $this->post('/reset-password', [])->assertNotFound();
    }

    public function test_authenticated_users_cannot_change_their_own_password_outside_user_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Lonas');

        $this->actingAs($user)->get('/password/force')->assertNotFound();

        Sanctum::actingAs($user);
        $this->putJson('/api/v1/auth/password', [
            'current_password' => 'password',
            'password' => 'NuevaClave456',
            'password_confirmation' => 'NuevaClave456',
        ])->assertStatus(405);
    }

    public function test_authorized_administrator_can_reset_password_from_user_panel(): void
    {
        $administrator = User::factory()->create();
        $administrator->assignRole('Admin');

        $user = User::factory()->create([
            'password' => Hash::make('Anterior123'),
            'must_change_password' => true,
        ]);
        $user->assignRole('Lonas');

        $response = $this->actingAs($administrator)
            ->put(route('settings.usuarios.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'NuevaClave456',
                'password_confirmation' => 'NuevaClave456',
                'role' => 'Lonas',
            ]);

        $response->assertRedirect(route('settings.usuarios.index'));

        $user->refresh();
        $this->assertTrue(Hash::check('NuevaClave456', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
    }
}
