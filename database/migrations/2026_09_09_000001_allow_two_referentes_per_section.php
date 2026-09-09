<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_UNIQUE = 'referentes_seccionales_cve_seccion_unique';
    private const SLOT_UNIQUE = 'referentes_seccionales_cve_seccion_posicion_unique';
    private const SLOT_CHECK = 'referentes_seccionales_posicion_check';

    public function up()
    {
        Schema::table('referentes_seccionales', function (Blueprint $table) {
            $table->dropUnique(self::OLD_UNIQUE);
            $table->unsignedTinyInteger('posicion')->default(1)->after('seccion');
            $table->unique(
                ['cve_mun', 'seccion', 'posicion'],
                self::SLOT_UNIQUE
            );
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(sprintf(
                'ALTER TABLE referentes_seccionales ADD CONSTRAINT %s CHECK (posicion IN (1, 2))',
                self::SLOT_CHECK
            ));
        }
    }

    public function down()
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(sprintf(
                'ALTER TABLE referentes_seccionales DROP CHECK %s',
                self::SLOT_CHECK
            ));
        }

        Schema::table('referentes_seccionales', function (Blueprint $table) {
            $table->dropUnique(self::SLOT_UNIQUE);
            $table->unique(['cve_mun', 'seccion'], self::OLD_UNIQUE);
            $table->dropColumn('posicion');
        });
    }
};
