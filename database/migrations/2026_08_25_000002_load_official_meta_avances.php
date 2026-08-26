<?php

use Database\Seeders\OfficialMetaAvancesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new OfficialMetaAvancesSeeder())->run();
    }

    public function down(): void
    {
        DB::table('meta_avances')
            ->where('tipo', 'convencidos')
            ->whereBetween('distrito_local', [1, 24])
            ->where('fecha_inicio', '2000-01-01')
            ->where('fecha_fin', '2099-12-31')
            ->delete();
    }
};
