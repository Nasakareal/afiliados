<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('referentes_seccionales', function (Blueprint $table) {
            $table->unique(
                ['cve_mun', 'seccion'],
                'referentes_seccionales_cve_seccion_unique'
            );
        });
    }

    public function down()
    {
        Schema::table('referentes_seccionales', function (Blueprint $table) {
            $table->dropUnique(
                'referentes_seccionales_cve_seccion_unique'
            );
        });
    }
};
