<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('meta_avances')
            ->where('tipo', 'estatal')
            ->update(['tipo' => 'convencidos']);
    }

    public function down(): void
    {
        DB::table('meta_avances')
            ->where('tipo', 'convencidos')
            ->update(['tipo' => 'estatal']);
    }
};
