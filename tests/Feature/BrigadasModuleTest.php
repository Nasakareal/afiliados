<?php

namespace Tests\Feature;

use App\Models\Brigada;
use App\Models\Seccion;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrigadasModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
        Storage::fake('local');
        DB::table('secciones')->insert([
            ['seccion' => '1234', 'cve_mun' => '053', 'municipio' => 'Morelia', 'distrito_local' => 10, 'distrito_federal' => 8],
            ['seccion' => '0001', 'cve_mun' => '001', 'municipio' => 'Acuitzio', 'distrito_local' => 18, 'distrito_federal' => 11],
        ]);
    }

    public function test_form_only_shows_morelia_sections_and_does_not_print_blade_expression(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)->get(route('brigadas.create'))
            ->assertOk()
            ->assertSeeText('Sección 1234')
            ->assertDontSeeText('Acuitzio')
            ->assertDontSee('seccion_id)===(string)', false);
    }

    public function test_user_can_see_brigade_and_register_attendance_with_photo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $section = Seccion::where('cve_mun', '053')->firstOrFail();
        $brigade = Brigada::create([
            'seccion_id' => $section->id, 'responsable' => 'Responsable de prueba',
            'direccion' => 'Centro de Morelia', 'lat' => 19.7026, 'lng' => -101.1922,
            'activo' => true, 'capturado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('brigadas.show', $brigade))
            ->assertOk()->assertSeeText('Confirmar mi asistencia');

        $this->actingAs($admin)->post(route('brigadas.participar', $brigade), [
            'foto' => UploadedFile::fake()->image('asistencia.jpg', 800, 600),
            'observaciones' => 'Presente en territorio',
        ])->assertRedirect(route('brigadas.show', $brigade));

        $participant = $brigade->participantes()->firstOrFail();
        Storage::disk('local')->assertExists($participant->evidencia_path);
        $this->actingAs($admin)->get(route('brigadas.participantes.evidencia', [$brigade, $participant]))->assertOk();

        $this->actingAs($admin)->post(route('brigadas.participar', $brigade), [
            'foto' => UploadedFile::fake()->image('duplicada.jpg'),
        ])->assertSessionHasErrors('foto');
        $this->assertSame(1, $brigade->participantes()->count());
    }

    public function test_operational_user_can_access_brigades_while_general_access_is_restricted(): void
    {
        config(['auth.restrict_system_access' => true]);
        $user = User::factory()->create();
        $user->assignRole('Capturista');
        $section = Seccion::where('cve_mun', '053')->firstOrFail();
        Brigada::create([
            'seccion_id' => $section->id, 'responsable' => 'Brigada visible',
            'lat' => 19.7, 'lng' => -101.19, 'activo' => true,
        ]);

        $this->actingAs($user)->get(route('brigadas.index'))
            ->assertOk()->assertSeeText('Brigada visible');
    }

    public function test_brigade_map_is_available_from_navigation_and_returns_points(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $section = Seccion::where('cve_mun', '053')->firstOrFail();
        $brigade = Brigada::create([
            'seccion_id' => $section->id,
            'responsable' => 'Brigada del mapa',
            'direccion' => 'Plaza principal',
            'lat' => 19.7026,
            'lng' => -101.1922,
            'activo' => true,
            'capturado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('brigadas.map'))
            ->assertOk()
            ->assertSeeText('Mapa de brigadas')
            ->assertSeeText('Las secciones resaltadas tienen brigadas registradas')
            ->assertSee('morelia-secciones.geojson')
            ->assertSeeText('Mapa de lonas');

        $this->actingAs($admin)->getJson(route('brigadas.map.data'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $brigade->id)
            ->assertJsonPath('0.seccion', '1234')
            ->assertJsonPath('0.responsable', 'Brigada del mapa')
            ->assertJsonPath('0.activo', true);
    }

    public function test_flutter_api_lists_morelia_and_registers_attendance(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $section = Seccion::where('cve_mun', '053')->firstOrFail();
        $brigade = Brigada::create([
            'seccion_id' => $section->id, 'responsable' => 'Brigada móvil',
            'direccion' => 'Morelia', 'lat' => 19.7, 'lng' => -101.19,
            'activo' => true, 'capturado_por' => $admin->id,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/brigadas')->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.seccion.numero', '1234')
            ->assertJsonPath('data.0.ya_participe', false);
        $this->getJson('/api/v1/brigadas/secciones')->assertOk()->assertJsonCount(1);

        $created = $this->withHeader('Accept', 'application/json')->post("/api/v1/brigadas/{$brigade->id}/participar", [
            'foto' => UploadedFile::fake()->image('flutter.jpg', 800, 600),
        ]);
        $created->assertCreated()->assertJsonPath('id', $brigade->participantes()->firstOrFail()->id);
        $this->getJson("/api/v1/brigadas/{$brigade->id}")
            ->assertOk()->assertJsonPath('ya_participe', true)->assertJsonPath('participantes_count', 1);
    }
}
