<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ElectoralIntelligenceProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_projection_uses_only_yes_affiliates_as_mobilizable_base(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Admin');

        $this->insertAffiliate($user->id, '1118', 'validado');
        $this->insertAffiliate($user->id, '1118', 'validado', 'Morelia', '053', 'Chapultepec Sur');
        $this->insertAffiliate($user->id, '1118', 'descartado');
        $this->insertAffiliate($user->id, '1200', 'validado');
        $this->insertAffiliate($user->id, '9999', 'validado', 'Morelos', '054');
        $this->insertAffiliate($user->id, '9998', 'validado');

        $response = $this->actingAs($user)
            ->get(route('electoral.data'))
            ->assertOk()
            ->assertJsonPath('operacion.registrados', 4)
            ->assertJsonPath('operacion.afiliados_si', 3)
            ->assertJsonPath('operacion.afiliados_no', 1)
            ->assertJsonPath('operacion.secciones_cubiertas', 2)
            ->assertJsonPath('operacion.registros_fuera_mapa', 1)
            ->assertJsonPath('operacion.sin_base_si', 400)
            ->assertJsonPath('operacion.estructura_minima', 2)
            ->assertJsonPath('indice_electoral.secciones_calculadas', 365)
            ->assertJsonPath('indice_electoral.secciones_sin_indice', 37)
            ->assertJsonPath('socioeconomico.secciones_con_datos', 373)
            ->assertJsonPath('socioeconomico.cobertura_alta', 317)
            ->assertHeader('Cache-Control', 'no-store, private');

        $section = collect($response->json('secciones'))->firstWhere('seccion', 1118);

        $this->assertSame(3, $section['operacion']['registrados']);
        $this->assertSame(2, $section['operacion']['afiliados_si']);
        $this->assertSame(1, $section['operacion']['afiliados_no']);
        $this->assertNotNull($section['socioeconomico']);
        $this->assertArrayHasKey('sin_internet', $section['socioeconomico']['indicadores']);
        $this->assertContains('Chapultepec Sur', $section['colonias']['nombres']);
        $this->assertFalse($section['colonias']['es_catalogo_completo']);

        $section1188 = collect($response->json('secciones'))->firstWhere('seccion', 1188);
        $this->assertSame(['Cinco de Diciembre'], $section1188['colonias']['nombres']);
        $this->assertSame('IEM, listado de ubicación de casillas 2025', $section1188['colonias']['fuente']);

        $this->get(route('electoral.index'))
            ->assertOk()
            ->assertSeeText('¿Cuántos afiliados podrían acudir?')
            ->assertSeeText('Podrían acudir')
            ->assertSeeText('Bajo · 60%')
            ->assertSeeText('¿En qué secciones hace falta más trabajo?')
            ->assertSeeText('¿Qué tan favorable se ve cada sección?')
            ->assertSeeText('IODE · Déficit o superávit de la sección')
            ->assertSeeText('¿En qué zona está cada sección?')
            ->assertSeeText('Zona C · sobreoferta · mayor de 1.25')
            ->assertSeeText('Matriz de secciones por zona IODE')
            ->assertSeeText('Secciones de Morelia')
            ->assertSeeText('Lectura y seguimiento')
            ->assertSeeText('no equivale a una preferencia electoral')
            ->assertSeeText('Abrir mapa completo')
            ->assertSeeText('Esa sección no aparece en el mapa vigente.')
            ->assertSeeText('Exportar todo a Excel')
            ->assertSeeText('Lista nominal')
            ->assertDontSeeText('% de la lista nominal')
            ->assertSeeText('¿Cómo son las condiciones de cada sección?')
            ->assertSeeText('Condiciones de vida · Viviendas sin internet')
            ->assertSeeText('Aquí verás sus colonias, resultados, afiliados y condiciones de vida.')
            ->assertSeeText('Contexto rápido de la sección')
            ->assertSeeText('Antes de levantar información')
            ->assertSeeText('Preguntas para escuchar a la comunidad')
            ->assertSeeText('no describe a cada persona ni permite suponer sus preferencias')
            ->assertSeeText('Ver primero las más importantes')
            ->assertSee('Afiliados confirmados por sección');
    }

    public function test_complete_electoral_intelligence_can_be_exported_to_excel(): void
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Admin');

        $response = $this->actingAs($user)
            ->get(route('electoral.export.xlsx', ['tasa' => 80]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $workbook = IOFactory::load($response->baseResponse->getFile()->getPathname());

        $this->assertSame([
            'Resumen',
            'Indice IODE',
            'Prioridades',
            'Historial electoral',
            'Movilizacion',
            'Condiciones 2020',
            'Colonias',
            'Fuentes y metodo',
        ], $workbook->getSheetNames());

        $priorities = $workbook->getSheetByName('Prioridades');
        $this->assertSame('IODE', $priorities->getCell('D4')->getValue());
        $this->assertSame('Lista nominal', $priorities->getCell('H4')->getValue());
        $this->assertSame('Tasa usada', $workbook->getSheetByName('Movilizacion')->getCell('G4')->getValue());
        $this->assertSame(80, $workbook->getSheetByName('Movilizacion')->getCell('G5')->getValue());

        $workbook->disconnectWorksheets();
    }

    private function insertAffiliate(
        int $capturistaId,
        string $section,
        string $status,
        string $municipality = 'Morelia',
        string $municipalityKey = '053',
        ?string $colony = null
    ): void
    {
        DB::table('afiliados')->insert([
            'capturista_id' => $capturistaId,
            'nombre' => 'Registro de prueba',
            'municipio' => $municipality,
            'cve_mun' => $municipalityKey,
            'seccion' => $section,
            'distrito_local' => 17,
            'distrito_federal' => 10,
            'colonia' => $colony,
            'estatus' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
