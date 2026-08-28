<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_local_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('distrito_local');
            $table->timestamps();

            $table->unique(['user_id', 'distrito_local']);
            $table->index('distrito_local');
        });

        DB::table('users')
            ->whereNotNull('distrito_local')
            ->orderBy('id')
            ->chunkById(500, function ($users) {
                $now = now();
                DB::table('user_local_districts')->insertOrIgnore(
                    $users->map(fn ($user) => [
                        'user_id' => $user->id,
                        'distrito_local' => $user->distrito_local,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_local_districts');
    }
};
