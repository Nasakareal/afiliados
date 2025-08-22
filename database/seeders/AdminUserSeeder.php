<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_SUPERADMIN_EMAIL', 'admin@admin.com');
        $pass  = env('SEED_SUPERADMIN_PASS',  '123');

        $u = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Super Admin', 'password' => bcrypt($pass)]
        );

        if (!$u->hasRole('SuperAdmin')) {
            $u->assignRole('SuperAdmin');
        }
    }
}
