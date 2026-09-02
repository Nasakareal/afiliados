<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_capturista_with_assigned_district_only_sees_own_captures_on_dashboard(): void
    {
        $this->seed(PermissionsSeeder::class);

        $capturista = User::factory()->create([
            'distrito_local' => 1,
            'must_change_password' => false,
        ]);
        $capturista->assignRole('Capturista');

        $otroCapturista = User::factory()->create([
            'distrito_local' => 1,
            'must_change_password' => false,
        ]);
        $otroCapturista->assignRole('Capturista');

        $this->insertAffiliate($capturista->id, 'Propia', '0101', 'validado');
        $this->insertAffiliate($capturista->id, 'No afiliada', '0102', 'descartado');
        $this->insertAffiliate($capturista->id, 'Eliminada', '0103', 'validado', now());
        $this->insertAffiliate($otroCapturista->id, 'Ajena uno', '0101', 'validado');
        $this->insertAffiliate($otroCapturista->id, 'Ajena dos', '0102', 'descartado');

        $response = $this->actingAs($capturista)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame(2, $response->viewData('stats')['total']);
        $this->assertSame(1, $response->viewData('stats')['validado']);
        $this->assertSame(1, $response->viewData('stats')['descartado']);
        $this->assertArrayNotHasKey('pendiente', $response->viewData('stats'));
        $response->assertDontSee('Pendientes')
            ->assertDontSee('Nuevos hoy')
            ->assertDontSee('Altas últimos 7 días');
        $this->assertSame(2, (int) $response->viewData('porMunicipio')->sum('total'));
        $this->assertSame(2, (int) $response->viewData('porSeccion')->sum('total'));
    }

    private function insertAffiliate(
        int $capturistaId,
        string $nombre,
        string $seccion,
        string $estatus,
        $deletedAt = null
    ): void
    {
        DB::table('afiliados')->insert([
            'capturista_id' => $capturistaId,
            'nombre' => $nombre,
            'municipio' => 'Morelia',
            'cve_mun' => '053',
            'seccion' => $seccion,
            'distrito_local' => 1,
            'distrito_federal' => 8,
            'estatus' => $estatus,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
    }
}
