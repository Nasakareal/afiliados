<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Actividad;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;

class ActividadSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_MX');

        // Usuario que crea las actividades
        $user = User::first() ?? User::factory()->create([
            'name' => 'Administrador Demo',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $estados = ['programada','cancelada','realizada'];
        $lugares = [
            'Auditorio UTM','Plaza Principal de Morelia','Casa de Cultura',
            'Oficina Central','Colonia Juárez','Teatro Morelos',
            'Parque Bicentenario','Sala de Juntas','Cancha Municipal',
            'Biblioteca Pública','Explanada Municipal','Centro Deportivo'
        ];

        $titulos = [
            'Reunión con coordinadores','Taller de capacitación',
            'Visita a la comunidad','Junta de planeación','Evento cultural',
            'Rueda de prensa','Campaña informativa','Reunión de seguimiento',
            'Actividad de voluntariado','Entrega de materiales'
        ];

        $inicioMes = Carbon::now()->startOfMonth();
        $finMesSiguiente = Carbon::now()->addMonth()->endOfMonth();
        $dias = $inicioMes->daysUntil($finMesSiguiente);

        foreach ($dias as $dia) {
            // máximo 3 actividades al día
            $numActs = rand(0, 3);

            for ($i=0; $i<$numActs; $i++) {
                $allDay = $faker->boolean(5); // 5% chance de que sea todo el día

                if ($allDay) {
                    $inicio = $dia->copy()->setTime(0,0);
                    $fin = null;
                } else {
                    $horaInicio = $faker->numberBetween(8, 18); // entre 8am y 6pm
                    $duracion   = $faker->numberBetween(1, 2);  // 1 o 2 horas
                    $inicio = $dia->copy()->setTime($horaInicio, 0);
                    $fin    = $inicio->copy()->addHours($duracion);
                }

                Actividad::create([
                    'titulo'      => $faker->randomElement($titulos),
                    'descripcion' => $faker->sentence(12, true),
                    'inicio'      => $inicio,
                    'fin'         => $fin,
                    'all_day'     => $allDay,
                    'lugar'       => $faker->randomElement($lugares),
                    'creado_por'  => $user->id,
                    'estado'      => $faker->randomElement($estados),
                ]);
            }
        }
    }
}
