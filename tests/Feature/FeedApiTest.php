<?php

namespace Tests\Feature;

use App\Models\Lona;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeedApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_feed_only_contains_lona_posts_and_uses_cursor_pagination(): void
    {
        $author = User::factory()->create(['must_change_password' => false]);
        foreach (range(1, 9) as $number) {
            Lona::create([
                'seccion' => (string) (1000 + $number),
                'direccion' => 'Dirección ' . $number,
                'responsable' => 'Responsable ' . $number,
                'lat' => 19.7,
                'lng' => -101.2,
                'foto_path' => 'lonas/test-' . $number . '.jpg',
                'capturado_por' => $author->id,
            ]);
        }

        $reader = User::factory()->create(['must_change_password' => false]);
        $reader->givePermissionTo('lonas.ver');
        Sanctum::actingAs($reader);

        $first = $this->getJson('/api/v1/feed?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'items')
            ->assertJsonPath('items.0.type', 'lona')
            ->assertJsonPath('has_more', true);

        $this->assertStringContainsString('variant=feed', $first->json('items.0.image_url'));

        $cursor = $first->json('next_cursor');
        $this->assertNotEmpty($cursor);

        $this->getJson('/api/v1/feed?per_page=4&cursor=' . $cursor)
            ->assertOk()
            ->assertJsonCount(4, 'items')
            ->assertJsonPath('items.0.type', 'lona');
    }

    public function test_feed_is_forbidden_without_lonas_permission(): void
    {
        Sanctum::actingAs(User::factory()->create(['must_change_password' => false]));

        $this->getJson('/api/v1/feed')->assertForbidden();
    }
}
