<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comunicados', function (Blueprint $table) {
            $table->index(['deleted_at'], 'idx_comunicados_deleted_at');
            $table->index(['estado', 'visible_desde', 'visible_hasta'], 'idx_comunicados_estado_ventana');
            $table->index(['visible_desde'], 'idx_comunicados_desde');
            $table->index(['visible_hasta'], 'idx_comunicados_hasta');
        });
    }

    public function down(): void
    {
        Schema::table('comunicados', function (Blueprint $table) {
            $table->dropIndex('idx_comunicados_deleted_at');
            $table->dropIndex('idx_comunicados_estado_ventana');
            $table->dropIndex('idx_comunicados_desde');
            $table->dropIndex('idx_comunicados_hasta');
        });
    }
};
