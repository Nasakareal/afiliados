<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->string('demo_batch', 64)->nullable()->after('observaciones');
            $table->index('demo_batch', 'idx_afiliados_demo_batch');
            $table->index(
                ['cve_mun', 'seccion', 'deleted_at'],
                'idx_afiliados_avance_scope'
            );
        });
    }

    public function down(): void
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->dropIndex('idx_afiliados_avance_scope');
            $table->dropIndex('idx_afiliados_demo_batch');
            $table->dropColumn('demo_batch');
        });
    }
};
