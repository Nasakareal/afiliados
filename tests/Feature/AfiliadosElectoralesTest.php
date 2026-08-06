<?php

namespace Tests\Feature;

use App\Http\Controllers\AfiliadoController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AfiliadosElectoralesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->string('seccion', 6);
            $table->string('municipio', 120)->nullable();
            $table->string('cve_mun', 3)->nullable();
            $table->integer('lista_nominal')->nullable();
            $table->integer('distrito_local')->nullable();
            $table->integer('distrito_federal')->nullable();
            $table->decimal('centroid_lat', 10, 7)->nullable();
            $table->decimal('centroid_lng', 10, 7)->nullable();
        });
        Schema::create('afiliados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('capturista_id');
            $table->string('nombre', 120);
            $table->string('apellido_paterno', 120)->nullable();
            $table->string('apellido_materno', 120)->nullable();
            $table->unsignedTinyInteger('edad')->nullable();
            $table->string('sexo')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('clave_elector', 30)->nullable()->unique();
            $table->string('tipo_vinculo', 10)->nullable();
            $table->string('numero_mov', 50)->nullable();
            $table->string('municipio', 120);
            $table->string('cve_mun', 3)->nullable();
            $table->string('localidad', 150)->nullable();
            $table->string('colonia', 150)->nullable();
            $table->string('calle', 150)->nullable();
            $table->string('numero_ext', 20)->nullable();
            $table->string('numero_int', 20)->nullable();
            $table->string('cp', 10)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('seccion', 6)->nullable();
            $table->unsignedSmallInteger('distrito_federal')->nullable();
            $table->unsignedSmallInteger('distrito_local')->nullable();
            $table->text('perfil')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estatus')->default('pendiente');
            $table->timestamp('fecha_convencimiento')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert(['id' => 1, 'name' => 'Prueba', 'created_at' => now(), 'updated_at' => now()]);
        Auth::shouldReceive('id')->andReturn(1);
    }

    public function test_los_campos_electorales_son_opcionales(): void
    {
        $response = $this->controller()->store($this->request('POST', $this->baseData()));

        $this->assertStringContainsString('/afiliados/1', $response->getTargetUrl());
        $this->assertDatabaseHas('afiliados', [
            'clave_elector' => null,
            'tipo_vinculo' => null,
            'numero_mov' => null,
        ]);
    }

    public function test_la_clave_se_normaliza_y_es_unica(): void
    {
        $this->controller()->store($this->request('POST', $this->baseData([
            'nombre' => 'PERSONA UNO',
            'clave_elector' => ' abcd 1234 ',
        ])));

        $this->assertDatabaseHas('afiliados', ['clave_elector' => 'ABCD1234']);

        try {
            $this->controller()->store($this->request('POST', $this->baseData([
                'nombre' => 'PERSONA DOS',
                'clave_elector' => 'abcd1234',
            ])));
            $this->fail('La clave duplicada debió fallar.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('clave_elector', $exception->errors());
        }
    }

    public function test_solo_mov_conserva_su_numero(): void
    {
        $this->controller()->store($this->request('POST', $this->baseData([
            'nombre' => 'MOVIMIENTO',
            'tipo_vinculo' => 'mov',
            'numero_mov' => '18',
        ])));
        $this->controller()->store($this->request('POST', $this->baseData([
            'nombre' => 'COMITE',
            'tipo_vinculo' => 'comite',
            'numero_mov' => '99',
        ])));

        $this->assertDatabaseHas('afiliados', ['nombre' => 'MOVIMIENTO', 'tipo_vinculo' => 'mov', 'numero_mov' => '18']);
        $this->assertDatabaseHas('afiliados', ['nombre' => 'COMITE', 'tipo_vinculo' => 'comite', 'numero_mov' => null]);
    }

    public function test_la_paginacion_admite_hasta_500_y_usa_25_por_defecto(): void
    {
        $this->insertarAfiliados(501);

        $view = $this->controller()->index($this->request('GET', ['per_page' => 500]));
        $this->assertCount(500, $view->getData()['afiliados']);
        $this->assertTrue($view->getData()['afiliados']->hasMorePages());

        $view = $this->controller()->index($this->request('GET', ['per_page' => 999]));
        $this->assertCount(25, $view->getData()['afiliados']);
    }

    public function test_el_pdf_exporta_solo_la_pagina_solicitada(): void
    {
        $this->insertarAfiliados(30);

        $response = $this->controller()->exportarPagina($this->request('GET', ['per_page' => 25, 'page' => 2]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('personas_convencidas_pagina_2_', $response->headers->get('content-disposition'));
    }

    private function baseData(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'PERSONA PRUEBA',
            'municipio' => 'Morelia',
            'cve_mun' => '053',
            'seccion' => '1234',
            'perfil' => 'REFERENTE',
            'estatus' => 'pendiente',
        ], $overrides);
    }

    private function insertarAfiliados(int $cantidad): void
    {
        $rows = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $rows[] = [
                'capturista_id' => 1,
                'nombre' => 'PERSONA '.$i,
                'clave_elector' => 'ELECTOR'.$i,
                'municipio' => 'Morelia',
                'cve_mun' => '053',
                'seccion' => '1234',
                'perfil' => 'REFERENTE',
                'estatus' => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) === 50 || $i === $cantidad) {
                DB::table('afiliados')->insert($rows);
                $rows = [];
            }
        }
    }

    private function controller(): AfiliadoController
    {
        return $this->app->make(AfiliadoController::class);
    }

    private function request(string $method, array $data): Request
    {
        $request = Request::create('/afiliados', $method, $data);
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);

        return $request;
    }
}
