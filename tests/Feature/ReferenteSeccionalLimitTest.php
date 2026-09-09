<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReferenteSeccionalLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'must_change_password' => false,
        ]);
        $this->admin->assignRole('Admin');
        foreach ([
            'referentes_seccionales.crear',
            'avance_seccional.ver',
        ] as $permission) {
            $this->admin->givePermissionTo(
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ])
            );
        }

        DB::table('secciones')->insert([
            'cve_mun' => '066',
            'municipio' => 'Pátzcuaro',
            'seccion' => '1234',
            'distrito_local' => 15,
            'distrito_federal' => 11,
        ]);
    }

    public function test_it_allows_two_referentes_and_rejects_a_third_for_the_same_section(): void
    {
        $this->actingAs($this->admin);

        $this->post(
            route('referentes_seccionales.store'),
            $this->payload('Primer referente')
        )->assertRedirect();

        $this->get(route('referentes_seccionales.create'))
            ->assertOk()
            ->assertViewHas('secciones', function ($secciones) {
                return $secciones->contains(
                    fn ($seccion) => $seccion->cve_mun === '066'
                        && $seccion->seccion === '1234'
                );
            });

        $this->post(
            route('referentes_seccionales.store'),
            $this->payload('Segundo referente')
        )->assertRedirect();

        $this->assertDatabaseCount('referentes_seccionales', 2);
        $this->assertEqualsCanonicalizing(
            [1, 2],
            DB::table('referentes_seccionales')
                ->pluck('posicion')
                ->map(fn ($posicion) => (int) $posicion)
                ->all()
        );

        $this->get(route('avance_seccional.index'))
            ->assertOk()
            ->assertSeeText('PRIMER REFERENTE')
            ->assertSeeText('SEGUNDO REFERENTE')
            ->assertSeeText('Completa 2/2');

        $this->get(route('referentes_seccionales.create'))
            ->assertOk()
            ->assertViewHas('secciones', function ($secciones) {
                return !$secciones->contains(
                    fn ($seccion) => $seccion->cve_mun === '066'
                        && $seccion->seccion === '1234'
                );
            });

        $this->from(route('referentes_seccionales.create'))
            ->post(
                route('referentes_seccionales.store'),
                $this->payload('Tercer referente')
            )
            ->assertRedirect(route('referentes_seccionales.create'))
            ->assertSessionHasErrors('seccion');

        $this->assertDatabaseCount('referentes_seccionales', 2);
    }

    private function payload(string $nombre): array
    {
        return [
            'cve_mun' => '066',
            'seccion' => '1234',
            'nombre_completo' => $nombre,
            'activo' => '1',
            'whatsapp' => '1',
        ];
    }
}
