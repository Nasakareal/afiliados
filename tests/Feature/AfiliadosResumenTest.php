<?php

namespace Tests\Feature;

use App\Services\AfiliadosResumenService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AfiliadosResumenTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_stays_exact_on_insert_update_soft_delete_restore_and_delete(): void
    {
        $user = User::factory()->create();
        $id = DB::table('afiliados')->insertGetId($this->affiliate($user->id));

        $this->assertSummary(1, 'validado');

        DB::table('afiliados')->where('id', $id)->update(['estatus' => 'descartado']);
        $this->assertSummary(1, 'descartado');
        $this->assertDatabaseMissing('afiliados_resumen', ['estatus' => 'validado']);

        DB::table('afiliados')->where('id', $id)->update(['deleted_at' => now()]);
        $this->assertSame(0, (int) DB::table('afiliados_resumen')->sum('total'));

        DB::table('afiliados')->where('id', $id)->update(['deleted_at' => null]);
        $this->assertSummary(1, 'descartado');

        DB::table('afiliados')->where('id', $id)->delete();
        $this->assertSame(0, (int) DB::table('afiliados_resumen')->sum('total'));
    }

    public function test_summary_can_be_rebuilt_from_source_records(): void
    {
        $user = User::factory()->create();
        DB::table('afiliados')->insert($this->affiliate($user->id));
        DB::table('afiliados_resumen')->update(['total' => 99]);

        $result = app(AfiliadosResumenService::class)->rebuild();

        $this->assertSame(1, $result['rows']);
        $this->assertSame(1, $result['total']);
        $this->assertSummary(1, 'validado');
    }

    private function assertSummary(int $total, string $estatus): void
    {
        $this->assertDatabaseHas('afiliados_resumen', [
            'cve_mun' => '053',
            'seccion' => '0101',
            'distrito_local' => 1,
            'capturista_id' => 1,
            'referente' => 'Gladyz Butanda',
            'estatus' => $estatus,
            'total' => $total,
        ]);
    }

    private function affiliate(int $userId): array
    {
        return [
            'capturista_id' => $userId,
            'nombre' => 'Persona de prueba',
            'municipio' => 'Morelia',
            'cve_mun' => '053',
            'seccion' => '0101',
            'distrito_local' => 1,
            'distrito_federal' => 8,
            'perfil' => ' Gladyz Butanda ',
            'estatus' => 'validado',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
