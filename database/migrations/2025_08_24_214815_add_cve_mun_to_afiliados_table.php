<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('afiliados', function (Blueprint $t) {
            if (!Schema::hasColumn('afiliados','cve_mun')) {
                $t->string('cve_mun',3)->nullable()->after('municipio');
                $t->index(['cve_mun']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('afiliados', function (Blueprint $t) {
            if (Schema::hasColumn('afiliados','cve_mun')) {
                $t->dropIndex(['cve_mun']);
                $t->dropColumn('cve_mun');
            }
        });
    }
};
