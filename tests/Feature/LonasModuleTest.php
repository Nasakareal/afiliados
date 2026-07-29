<?php

namespace Tests\Feature;

use App\Models\Lona;
use App\Models\User;
use Database\Seeders\LonasUsersSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LonasModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_lonas_role_only_has_access_to_the_lonas_module(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Lonas');

        $this->actingAs($user)->get(route('lonas.index'))->assertOk();
        $this->actingAs($user)->get(route('lonas.create'))->assertOk();
        $this->actingAs($user)->get(route('lonas.map'))->assertOk();
        $this->actingAs($user)->get(route('afiliados.index'))->assertForbidden();
        $this->actingAs($user)->get(route('mapa.index'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('lonas.index'));
    }

    public function test_user_can_capture_a_lona_and_photo_is_normalized_to_jpeg(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Lonas');

        $response = $this->actingAs($user)->post(route('lonas.store'), [
            'seccion' => '1234',
            'direccion' => 'Av. Madero 100, Centro',
            'ubicacion_google' => 'https://www.google.com/maps?q=19.7026,-101.1922',
            'lat' => '19.7026000',
            'lng' => '-101.1922000',
            'responsable' => 'Responsable de prueba',
            'foto' => UploadedFile::fake()->image('lona-pesada.png', 2400, 1600),
        ]);

        $lona = Lona::latest('id')->firstOrFail();
        $response->assertRedirect(route('lonas.show', $lona));
        $this->assertSame($user->id, $lona->capturado_por);
        $this->assertStringEndsWith('.jpg', $lona->foto_path);
        $this->assertGreaterThan(0, $lona->foto_bytes_final);
        Storage::disk('local')->assertExists($lona->foto_path);

        $image = getimagesize(Storage::disk('local')->path($lona->foto_path));
        $this->assertSame('image/jpeg', $image['mime']);
        $this->assertLessThanOrEqual(1920, max($image[0], $image[1]));
    }

    public function test_generic_lonas_seeder_creates_thirty_restricted_users(): void
    {
        $this->seed(LonasUsersSeeder::class);

        $users = User::where('email', 'like', '%@gladyadorez.com')->get();
        $this->assertCount(30, $users);
        $this->assertCount(24, $users->filter(fn (User $user) => strpos($user->email, 'distrito') === 0));
        $this->assertCount(6, $users->filter(fn (User $user) => strpos($user->email, 'coordinador') === 0));
        $this->assertTrue($users->contains('email', 'distrito01@gladyadorez.com'));
        $this->assertTrue($users->contains('email', 'coordinador01@gladyadorez.com'));

        foreach ($users as $user) {
            $this->assertTrue($user->hasExactRoles('Lonas'));
            $this->assertTrue($user->can('lonas.ver'));
            $this->assertTrue($user->can('lonas.crear'));
            $this->assertFalse($user->can('afiliados.ver'));
        }
    }
}
