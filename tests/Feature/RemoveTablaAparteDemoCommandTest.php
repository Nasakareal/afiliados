<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DemoTablaAparte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RemoveTablaAparteDemoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_removes_only_the_demo_batch(): void
    {
        $capturista = User::factory()->create();

        DB::table('afiliados')->insert([
            [
                'capturista_id' => $capturista->id,
                'nombre' => 'Registro real',
                'municipio' => 'Morelia',
                'demo_batch' => null,
            ],
            [
                'capturista_id' => $capturista->id,
                'nombre' => 'Registro temporal',
                'municipio' => 'Morelia',
                'demo_batch' => DemoTablaAparte::MARKER,
            ],
        ]);

        $this->artisan('demo:tabla-aparte:remove')
            ->expectsOutput('Demo eliminada: 1 afiliados retirados.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('afiliados', ['nombre' => 'Registro real']);
        $this->assertDatabaseMissing('afiliados', ['nombre' => 'Registro temporal']);
    }
}
