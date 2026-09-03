<?php

namespace App\Console\Commands;

use App\Services\AfiliadosResumenService;
use Illuminate\Console\Command;

class RebuildAfiliadosResumen extends Command
{
    protected $signature = 'afiliados:reconstruir-resumen';

    protected $description = 'Reconstruye los acumulados usados por Dashboard y Avance';

    public function handle(AfiliadosResumenService $service): int
    {
        $result = $service->rebuild();

        $this->info(sprintf(
            'Resumen reconstruido: %s combinaciones, %s convencidos activos.',
            number_format($result['rows']),
            number_format($result['total'])
        ));

        return self::SUCCESS;
    }
}
