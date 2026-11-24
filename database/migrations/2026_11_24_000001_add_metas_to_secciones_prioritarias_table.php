<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('secciones_prioritarias', function (Blueprint $table) {
            $table->unsignedInteger('meta_convencidos')->default(0)->after('prioridad');
            $table->unsignedInteger('meta_lonas')->default(0)->after('meta_convencidos');
        });
    }

    public function down(): void
    {
        Schema::table('secciones_prioritarias', function (Blueprint $table) {
            $table->dropColumn(['meta_convencidos', 'meta_lonas']);
        });
    }
};
