<?php

namespace Tests\Feature;

use App\Models\Lona;
use App\Models\User;
use Database\Seeders\DistrictCoordinatorUsersSeeder;
use Database\Seeders\LonasUsersSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LonasModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_lonas_role_only_has_access_to_the_lonas_module(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Lonas');

        $this->actingAs($user)->get(route('lonas.index'))->assertOk();
        $this->actingAs($user)->get(route('lonas.create'))->assertOk();
        $this->actingAs($user)->get(route('lonas.map'))->assertOk();
        $this->actingAs($user)->get(route('afiliados.index'))->assertForbidden();
        $this->actingAs($user)->get(route('mapa.index'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('lonas.index'));
    }

    public function test_user_can_capture_a_lona_and_photo_is_normalized_to_jpeg(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Lonas');

        $response = $this->actingAs($user)->post(route('lonas.store'), [
            'seccion' => '1234',
            'direccion' => 'Av. Madero 100, Centro',
            'ubicacion_google' => 'https://www.google.com/maps?q=19.7026,-101.1922',
            'lat' => '19.7026000',
            'lng' => '-101.1922000',
            'responsable' => 'Responsable de prueba',
            'foto' => UploadedFile::fake()->image('lona-pesada.png', 2400, 1600),
        ]);

        $lona = Lona::latest('id')->firstOrFail();
        $response->assertRedirect(route('lonas.show', $lona));
        $this->assertSame($user->id, $lona->capturado_por);
        $this->assertStringEndsWith('.jpg', $lona->foto_path);
        $this->assertGreaterThan(0, $lona->foto_bytes_final);
        Storage::disk('local')->assertExists($lona->foto_path);

        $image = getimagesize(Storage::disk('local')->path($lona->foto_path));
        $this->assertSame('image/jpeg', $image['mime']);
        $this->assertLessThanOrEqual(1920, max($image[0], $image[1]));
    }

    public function test_google_place_link_without_scheme_uses_place_coordinates_instead_of_viewport(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Lonas');
        $googleUrl = 'google.com/maps/place/Monumento+A+Lázaro+Cárdenas/@19.6751315,-101.2289137,13.17z/data=!4m5!3m4!1s0x842d0f18b7980341:0x645a16a9ec7eb00c!8m2!3d19.701789!4d-101.2071705?hl=es&entry=ttu';

        $response = $this->actingAs($user)->post(route('lonas.store'), [
            'seccion' => '1234',
            'direccion' => 'Monumento a Lázaro Cárdenas',
            'ubicacion_google' => $googleUrl,
            // Simulates the incorrect viewport coordinates previously selected by the browser.
            'lat' => '19.6751315',
            'lng' => '-101.2289137',
            'responsable' => 'Responsable de prueba',
            'foto' => UploadedFile::fake()->image('lona.jpg', 800, 600),
        ]);

        $lona = Lona::latest('id')->firstOrFail();
        $response->assertRedirect(route('lonas.show', $lona));
        $this->assertSame('https://'.$googleUrl, $lona->ubicacion_google);
        $this->assertEqualsWithDelta(19.701789, $lona->lat, 0.0000001);
        $this->assertEqualsWithDelta(-101.2071705, $lona->lng, 0.0000001);
    }

    public function test_lonas_excel_exports_all_filtered_rows_with_their_photos(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Lonas');

        $photo = UploadedFile::fake()->image('lona.jpg', 1600, 1200);
        Storage::disk('local')->put('lonas/prueba.jpg', file_get_contents($photo->getRealPath()));

        foreach (range(1, 121) as $number) {
            Lona::create([
                'seccion' => '0012',
                'direccion' => 'Avenida Centro '.$number,
                'ubicacion_google' => 'https://www.google.com/maps?q=19.7026,-101.1922',
                'lat' => 19.7026,
                'lng' => -101.1922,
                'foto_path' => 'lonas/prueba.jpg',
                'foto_nombre_original' => 'lona-'.$number.'.jpg',
                'foto_bytes_original' => 12345,
                'foto_bytes_final' => 6789,
                'responsable' => 'Responsable '.$number,
                'capturado_por' => $user->id,
            ]);
        }

        Lona::create([
            'seccion' => '9999',
            'direccion' => 'Otra ubicación',
            'ubicacion_google' => 'https://www.google.com/maps?q=20,-100',
            'lat' => 20,
            'lng' => -100,
            'foto_path' => 'lonas/prueba.jpg',
            'responsable' => 'No debe exportarse',
            'capturado_por' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('lonas.export.xlsx', [
            'q' => 'Centro',
            'seccion' => '0012',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertLessThan(5 * 1024 * 1024, filesize($path));
        $workbook = IOFactory::load($path);
        $sheet = $workbook->getActiveSheet();

        $this->assertSame('Listado de lonas', $sheet->getCell('A1')->getValue());
        $this->assertSame('Sección', $sheet->getCell('C4')->getValue());
        $this->assertSame('0012', $sheet->getCell('C5')->getValue());
        $this->assertStringStartsWith('Avenida Centro ', $sheet->getCell('D5')->getValue());
        $this->assertSame('Capturista', $sheet->getCell('G4')->getValue());
        $this->assertSame($user->name, $sheet->getCell('G5')->getValue());
        $this->assertSame(125, $sheet->getHighestDataRow());
        $this->assertCount(121, $sheet->getDrawingCollection());
        $this->assertLessThanOrEqual(145, $sheet->getDrawingCollection()[0]->getWidth());
        $this->assertLessThanOrEqual(100, $sheet->getDrawingCollection()[0]->getHeight());
        $this->assertSame(
            'https://www.google.com/maps?q=19.7026,-101.1922',
            $sheet->getCell('J5')->getHyperlink()->getUrl()
        );

        $workbook->disconnectWorksheets();
        @unlink($path);
    }

    public function test_generic_lonas_seeder_creates_thirty_restricted_users(): void
    {
        $this->seed(LonasUsersSeeder::class);

        $users = User::where('email', 'like', 'lonas%@gladyadorez.com')->get();
        $this->assertCount(30, $users);
        $this->assertTrue($users->contains('email', 'lonas01@gladyadorez.com'));
        $this->assertTrue($users->contains('email', 'lonas30@gladyadorez.com'));

        foreach ($users as $user) {
            $this->assertTrue(Hash::check('Lonas2026!', $user->password));
            $this->assertFalse($user->must_change_password);
            $this->assertTrue($user->hasExactRoles('Lonas'));
            $this->assertTrue($user->can('lonas.ver'));
            $this->assertTrue($user->can('lonas.crear'));
            $this->assertFalse($user->can('afiliados.ver'));
        }
    }

    public function test_district_seeder_does_not_replace_lonas_accounts(): void
    {
        $lonasUser = User::factory()->create([
            'name' => 'Captura Lonas 01',
            'email' => 'lonas01@gladyadorez.com',
            'must_change_password' => false,
        ]);

        $this->seed(DistrictCoordinatorUsersSeeder::class);

        $districtUser = User::where('email', 'distrito01@gladyadorez.com')->firstOrFail();
        $preservedLonasUser = User::where('email', 'lonas01@gladyadorez.com')->firstOrFail();

        $this->assertNotSame($lonasUser->id, $districtUser->id);
        $this->assertSame($lonasUser->id, $preservedLonasUser->id);
        $this->assertSame('Distrito Local 01', $districtUser->name);
        $this->assertFalse($districtUser->must_change_password);
        $this->assertTrue($districtUser->hasExactRoles('Lonas'));
        $this->assertSame(
            30,
            User::where('email', 'like', 'distrito%@gladyadorez.com')
                ->orWhere('email', 'like', 'coordinador%@gladyadorez.com')
                ->count()
        );
    }

    public function test_lonas_and_district_accounts_can_coexist(): void
    {
        $this->seed(LonasUsersSeeder::class);
        $this->seed(DistrictCoordinatorUsersSeeder::class);

        $this->assertSame(
            60,
            User::where('email', 'like', '%@gladyadorez.com')->count()
        );
    }
}
