<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AfiliadosSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_MX');

        $municipios = [
            ['cve_mun'=>'053', 'municipio'=>'Morelia', 'lat'=>19.7, 'lng'=>-101.19],
            ['cve_mun'=>'088', 'municipio'=>'Tarímbaro', 'lat'=>19.8, 'lng'=>-101.12],
            ['cve_mun'=>'039', 'municipio'=>'Huetamo', 'lat'=>18.63, 'lng'=>-100.90],
            ['cve_mun'=>'020', 'municipio'=>'Charo', 'lat'=>19.75, 'lng'=>-101.05],
            ['cve_mun'=>'027', 'municipio'=>'Copándaro', 'lat'=>19.87, 'lng'=>-101.22],
            ['cve_mun'=>'045', 'municipio'=>'Lázaro Cárdenas', 'lat'=>17.96, 'lng'=>-102.20],
            ['cve_mun'=>'079', 'municipio'=>'Pátzcuaro', 'lat'=>19.52, 'lng'=>-101.61],
            ['cve_mun'=>'065', 'municipio'=>'Zinapécuaro', 'lat'=>19.86, 'lng'=>-100.85],
            ['cve_mun'=>'010', 'municipio'=>'Apatzingán', 'lat'=>19.08, 'lng'=>-102.35],
            ['cve_mun'=>'034', 'municipio'=>'Uruapan', 'lat'=>19.42, 'lng'=>-102.06],
        ];

        $data = [];

        for ($i = 0; $i < 1000; $i++) {
            $m = $faker->randomElement($municipios);

            $data[] = [
                'capturista_id'   => 1,
                'nombre'          => $faker->firstName,
                'apellido_paterno'=> $faker->lastName,
                'apellido_materno'=> $faker->lastName,
                'edad'            => $faker->numberBetween(18, 80),
                'sexo'            => $faker->randomElement(['M','F']),
                'telefono'        => $faker->numerify('443#######'),
                'email'           => $faker->unique()->safeEmail,
                'municipio'       => $m['municipio'],
                'cve_mun'         => $m['cve_mun'],
                'localidad'       => $faker->city,
                'colonia'         => $faker->streetName,
                'calle'           => $faker->streetName,
                'numero_ext'      => $faker->buildingNumber,
                'numero_int'      => null,
                'cp'              => $faker->postcode,
                'lat'             => $m['lat'] + $faker->randomFloat(4, -0.05, 0.05),
                'lng'             => $m['lng'] + $faker->randomFloat(4, -0.05, 0.05),
                'seccion'         => $faker->numerify('####'),
                'distrito_federal'=> $faker->numberBetween(1, 12),
                'distrito_local'  => $faker->numberBetween(1, 24),
                'perfil'          => $faker->sentence(6),
                'observaciones'   => $faker->optional()->sentence(8),
                'estatus'         => $faker->randomElement(['pendiente','validado','descartado']),
                'fecha_convencimiento' => $faker->dateTimeThisDecade(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        DB::table('afiliados')->insert($data);
    }
}
