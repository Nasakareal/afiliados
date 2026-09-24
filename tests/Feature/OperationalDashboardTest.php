<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DemoOperationalKpis;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_operational_dashboard_is_only_rendered_for_admin_roles(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('Admin');
        $capturer = User::factory()->create(['must_change_password' => false]);
        $capturer->assignRole('Capturista');

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Centro de control operativo')
            ->assertSeeText('Acceso administrativo')
            ->assertSeeText('Reportes del equipo')
            ->assertDontSeeText('PERSONAS ÚNICAS');

        $this->actingAs($capturer)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Centro de control operativo')
            ->assertDontSeeText('Desempeño por responsable');
    }

    public function test_capturer_can_report_an_unofficial_activity_and_confirm_attendance(): void
    {
        Storage::fake('local');
        $capturer = User::factory()->create(['must_change_password' => false, 'distrito_local' => 17]);
        $capturer->assignRole('Capturista');

        $this->actingAs($capturer)->get(route('actividades.reportar'))
            ->assertOk()->assertSeeText('Sube una actividad que tú realizaste');

        $response = $this->actingAs($capturer)->post(route('actividades.reportes.store'), [
            'titulo' => 'Gestión vecinal propia',
            'tipo' => 'Gestión',
            'colonia' => 'Centro',
            'inicio' => now()->subDay()->format('Y-m-d H:i:s'),
            'asistentes' => 14,
            'yo_asisti' => 1,
            'evidencia' => UploadedFile::fake()->image('evidencia.jpg'),
        ]);

        $activity = DB::table('actividades')->where('titulo', 'Gestión vecinal propia')->first();
        $this->assertNotNull($activity);
        $response->assertRedirect(route('actividades.show', $activity->id));
        $this->assertSame('reporte_usuario', $activity->origen);
        $this->assertSame('pendiente', $activity->estado_revision);
        $this->assertSame(14, $activity->asistentes);
        $this->assertDatabaseHas('actividad_participantes', [
            'actividad_id' => $activity->id,
            'user_id' => $capturer->id,
        ]);
        Storage::disk('local')->assertExists($activity->evidencia_path);
    }

    public function test_demo_commands_only_create_and_remove_marked_records(): void
    {
        DB::table('users')->insert([
            'name' => 'Usuario real',
            'email' => 'real@example.test',
            'password' => bcrypt('password'),
            'must_change_password' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, Artisan::call('demo:kpis:load'));
        $this->assertSame(192, DB::table('afiliados')->where('demo_batch', DemoOperationalKpis::MARKER)->count());
        $this->assertSame(12, DB::table('actividades')->where('demo_batch', DemoOperationalKpis::MARKER)->count());
        $this->assertSame(4, DB::table('users')->where('demo_batch', DemoOperationalKpis::MARKER)->count());

        $this->assertSame(0, Artisan::call('demo:kpis:remove'));
        $this->assertSame(0, DB::table('afiliados')->where('demo_batch', DemoOperationalKpis::MARKER)->count());
        $this->assertSame(0, DB::table('actividades')->where('demo_batch', DemoOperationalKpis::MARKER)->count());
        $this->assertDatabaseHas('users', ['email' => 'real@example.test']);
    }
}
