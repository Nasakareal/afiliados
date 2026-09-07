<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SqlBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_upload_list_and_download_a_sql_backup(): void
    {
        Storage::fake('local');
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $this->actingAs($user)
            ->post(route('settings.backups_sql.upload'), [
                'backup' => UploadedFile::fake()->createWithContent('antes de borrar.sql', '-- respaldo'),
            ])
            ->assertRedirect(route('settings.backups_sql.index'));

        Storage::disk('local')->assertExists('backups_sql/antes_de_borrar.sql');

        $this->actingAs($user)
            ->get(route('settings.backups_sql.index'))
            ->assertOk()
            ->assertSee('antes_de_borrar.sql');

        $this->actingAs($user)
            ->get(route('settings.backups_sql.download', ['file' => 'antes_de_borrar.sql']))
            ->assertOk()
            ->assertDownload('antes_de_borrar.sql');
    }

    public function test_non_superadmin_cannot_access_sql_backups(): void
    {
        Storage::fake('local');
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Capturista');

        $this->actingAs($user)
            ->get(route('settings.backups_sql.index'))
            ->assertForbidden();
    }

    public function test_upload_rejects_non_sql_files(): void
    {
        Storage::fake('local');
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $this->actingAs($user)
            ->post(route('settings.backups_sql.upload'), [
                'backup' => UploadedFile::fake()->createWithContent('datos.txt', 'no sql'),
            ])
            ->assertRedirect(route('settings.backups_sql.index'))
            ->assertSessionHas('error');

        Storage::disk('local')->assertMissing('backups_sql/datos.txt');
    }
}
