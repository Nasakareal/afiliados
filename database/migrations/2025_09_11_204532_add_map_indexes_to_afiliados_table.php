<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMapIndexesToAfiliadosTable extends Migration
{
    public function up()
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->index(['deleted_at', 'cve_mun'], 'idx_afiliados_deleted_cve_mun');
            $table->index(['deleted_at', 'estatus', 'cve_mun'], 'idx_afiliados_deleted_estatus_cve_mun');

            $table->index(['deleted_at', 'lat', 'lng'], 'idx_afiliados_deleted_lat_lng');

            $table->index(['deleted_at'], 'idx_afiliados_deleted_at');
        });
    }

    public function down()
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->dropIndex('idx_afiliados_deleted_cve_mun');
            $table->dropIndex('idx_afiliados_deleted_estatus_cve_mun');
            $table->dropIndex('idx_afiliados_deleted_lat_lng');
            $table->dropIndex('idx_afiliados_deleted_at');
        });
    }
}
