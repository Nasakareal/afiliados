<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('secciones_prioritarias', function (Blueprint $table) {
            $table->id();
            $table->string('cve_mun', 3)->default('053');
            $table->string('seccion', 6);
            $table->unsignedTinyInteger('prioridad');
            $table->foreignId('asignado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cve_mun', 'seccion']);
            $table->index(['cve_mun', 'prioridad']);
        });

        $path = database_path('data/electoral/morelia_priority_colonies_reference.json');
        if (!is_file($path)) {
            return;
        }

        $reference = json_decode(file_get_contents($path), true);
        $now = now();
        $rows = collect($reference['filas'] ?? [])->map(fn(array $row) => [
            'cve_mun' => '053',
            'seccion' => str_pad((string) $row['seccion'], 4, '0', STR_PAD_LEFT),
            'prioridad' => (int) $row['prioridad'],
            'asignado_por' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            DB::table('secciones_prioritarias')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('secciones_prioritarias');
    }
};
