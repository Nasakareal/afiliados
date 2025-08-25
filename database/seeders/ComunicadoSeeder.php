<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comunicado;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;

class ComunicadoSeeder extends Seeder
{
    public function run(): void
    {
        // Necesitamos al menos 1 usuario para "creado_por"
        $creator = User::query()->first();

        if (!$creator) {
            // Evita el nullsafe (?->): esto funciona en PHP 7.x
            if (property_exists($this, 'command') && $this->command) {
                $this->command->warn('No hay usuarios en la tabla users. Crea al menos 1 antes de correr ComunicadoSeeder.');
            } else {
                echo "No hay usuarios en la tabla users. Crea al menos 1 antes de correr ComunicadoSeeder.\n";
            }
            return;
        }

        $ahora = Carbon::now();

        $rows = [
            [
                'creado_por'    => $creator->id,
                'titulo'        => 'Bienvenida al sistema',
                'contenido'     => "¡Gracias por usar el sistema!\n\nCualquier duda con soporte.",
                'visible_desde' => $ahora->copy()->subDay(),
                'visible_hasta' => null,
                'estado'        => 'publicado',
                'filtros'       => ['roles' => ['capturista','admin']],
            ],
            [
                'creado_por'    => $creator->id,
                'titulo'        => 'Mantenimiento programado',
                'contenido'     => "Habrá ventana de mantenimiento el domingo a las 02:00.",
                'visible_desde' => $ahora->copy()->subHours(6),
                'visible_hasta' => $ahora->copy()->addDays(7),
                'estado'        => 'publicado',
                'filtros'       => ['municipios' => ['Morelia','Tarímbaro']],
            ],
            [
                'creado_por'    => $creator->id,
                'titulo'        => 'Borrador de lineamientos',
                'contenido'     => "Borrador en revisión. No difundir.",
                'visible_desde' => null,
                'visible_hasta' => null,
                'estado'        => 'borrador',
                'filtros'       => null,
            ],
            [
                'creado_por'    => $creator->id,
                'titulo'        => 'Comunicación anterior (archivada)',
                'contenido'     => "Este comunicado ya no es visible.",
                'visible_desde' => $ahora->copy()->subMonths(2),
                'visible_hasta' => $ahora->copy()->subMonth(),
                'estado'        => 'archivado',
                'filtros'       => null,
            ],
        ];

        // Crear comunicados (casts del modelo guardan 'filtros' como json si es array)
        $comunicados = [];
        foreach ($rows as $row) {
            $comunicados[] = Comunicado::create(Arr::only($row, [
                'creado_por','titulo','contenido','visible_desde','visible_hasta','estado','filtros'
            ]));
        }

        // Marcar algunos como leídos (si hay usuarios)
        $users = User::query()->orderBy('id')->limit(5)->get();
        if ($users->count()) {
            $publicados = array_values(array_filter($comunicados, function ($c) {
                return $c && $c->estado === 'publicado';
            }));

            $u0 = $users->get(0);
            $u1 = $users->get(1);

            $leidoAt = Carbon::now()->subMinutes(10);

            if (isset($publicados[0]) && $u0) {
                $payload = [$u0->id => ['leido_at' => $leidoAt]];
                if ($u1) $payload[$u1->id] = ['leido_at' => $leidoAt];
                $publicados[0]->lectores()->syncWithoutDetaching($payload);
            }

            if (isset($publicados[1]) && $u0) {
                $publicados[1]->lectores()->syncWithoutDetaching([
                    $u0->id => ['leido_at' => $leidoAt],
                ]);
            }
        }

        if (property_exists($this, 'command') && $this->command) {
            $this->command->info('ComunicadoSeeder: comunicados de ejemplo creados.');
        } else {
            echo "ComunicadoSeeder: comunicados de ejemplo creados.\n";
        }
    }
}
