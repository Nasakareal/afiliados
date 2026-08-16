<?php

namespace Tests\Feature;

use App\Models\Lona;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LonaMapApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_map_only_returns_lonas_inside_visible_bounds(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('lonas.ver');
        Sanctum::actingAs($user);

        $inside = Lona::create([
            'seccion' => '1001',
            'direccion' => 'Morelia',
            'responsable' => 'Dentro',
            'lat' => 19.70,
            'lng' => -101.20,
            'foto_path' => 'lonas/dentro.jpg',
        ]);
        $outside = Lona::create([
            'seccion' => '2002',
            'direccion' => 'Fuera',
            'responsable' => 'Fuera',
            'lat' => 20.50,
            'lng' => -102.30,
            'foto_path' => 'lonas/fuera.jpg',
        ]);

        $this->getJson('/api/v1/lonas/mapa?bbox=-101.3,19.6,-101.1,19.8&limit=25')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $inside->id)
            ->assertJsonMissing(['id' => $outside->id]);
    }

    public function test_feed_photo_variant_is_generated_at_a_bounded_size(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('lonas.ver');
        Sanctum::actingAs($user);

        $source = imagecreatetruecolor(1400, 1000);
        ob_start();
        imagejpeg($source, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($source);
        Storage::disk('local')->put('lonas/original.jpg', $jpeg);

        $lona = Lona::create([
            'seccion' => '3003',
            'direccion' => 'Foto de feed',
            'responsable' => 'Responsable',
            'lat' => 19.70,
            'lng' => -101.20,
            'foto_path' => 'lonas/original.jpg',
        ]);

        $this->get("/api/v1/lonas/{$lona->id}/foto?variant=feed")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        Storage::disk('local')->assertExists("lonas/feed/{$lona->id}.jpg");
        [$width, $height] = getimagesize(Storage::disk('local')->path("lonas/feed/{$lona->id}.jpg"));
        $this->assertLessThanOrEqual(960, max($width, $height));
    }
}
