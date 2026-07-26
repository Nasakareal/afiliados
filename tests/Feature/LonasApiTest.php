<?php

namespace Tests\Feature;

use App\Models\Lona;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LonasApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_lonas_user_can_capture_list_map_and_read_photo_through_api(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Lonas');
        Sanctum::actingAs($user);

        $created = $this->withHeader('Accept', 'application/json')->post('/api/v1/lonas', [
            'seccion' => '1234',
            'direccion' => 'Av. Madero 100, Centro',
            'lat' => '19.7026000',
            'lng' => '-101.1922000',
            'responsable' => 'Responsable móvil',
            'foto' => UploadedFile::fake()->image('lona-movil.jpg', 1600, 1200),
        ]);

        $created->assertCreated()
            ->assertJsonPath('seccion', '1234')
            ->assertJsonPath('responsable', 'Responsable móvil');

        $lona = Lona::firstOrFail();
        $this->assertSame($user->id, $lona->capturado_por);
        Storage::disk('local')->assertExists($lona->foto_path);

        $this->getJson('/api/v1/lonas')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $lona->id);

        $this->getJson('/api/v1/lonas/mapa')
            ->assertOk()
            ->assertJsonPath('0.id', $lona->id);

        $this->getJson("/api/v1/lonas/{$lona->id}")
            ->assertOk()
            ->assertJsonPath('id', $lona->id);

        $this->get("/api/v1/lonas/{$lona->id}/foto")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_api_rejects_user_without_lonas_permission(): void
    {
        Sanctum::actingAs(User::factory()->create(['must_change_password' => false]));

        $this->getJson('/api/v1/lonas')->assertForbidden();
        $this->postJson('/api/v1/lonas', [])->assertForbidden();
    }
}
