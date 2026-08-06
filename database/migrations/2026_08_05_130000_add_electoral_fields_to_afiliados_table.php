<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->string('clave_elector', 30)->nullable()->unique()->after('email');
            $table->string('tipo_vinculo', 10)->nullable()->index()->after('clave_elector');
            $table->string('numero_mov', 50)->nullable()->after('tipo_vinculo');
        });
    }

    public function down(): void
    {
        Schema::table('afiliados', function (Blueprint $table) {
            $table->dropUnique(['clave_elector']);
            $table->dropIndex(['tipo_vinculo']);
            $table->dropColumn(['clave_elector', 'tipo_vinculo', 'numero_mov']);
        });
    }
};
