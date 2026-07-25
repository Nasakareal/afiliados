<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LonasUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_LONAS_PASSWORD', 'Lonas2026!');

        for ($number = 1; $number <= 30; $number++) {
            $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $email = "lonas{$suffix}@gladyadorez.com";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Captura Lonas {$suffix}",
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'must_change_password' => true,
                ]
            );

            $user->syncRoles(['Lonas']);
        }
    }
}
