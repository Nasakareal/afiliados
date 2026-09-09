<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('afiliados', function (Blueprint $table): void {
            $table->string('import_batch', 64)->nullable()->after('demo_batch');
            $table->index('import_batch', 'idx_afiliados_import_batch');
        });
    }

    public function down(): void
    {
        Schema::table('afiliados', function (Blueprint $table): void {
            $table->dropIndex('idx_afiliados_import_batch');
            $table->dropColumn('import_batch');
        });
    }
};
