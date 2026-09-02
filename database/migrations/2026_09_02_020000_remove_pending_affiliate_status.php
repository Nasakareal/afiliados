<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RemovePendingAffiliateStatus extends Migration
{
    public function up(): void
    {
        DB::table('afiliados')
            ->where('estatus', 'pendiente')
            ->update(['estatus' => 'validado']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE afiliados MODIFY estatus ENUM('validado','descartado') NOT NULL DEFAULT 'validado'"
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE afiliados MODIFY estatus ENUM('pendiente','validado','descartado') NOT NULL DEFAULT 'pendiente'"
            );
        }
    }
}
