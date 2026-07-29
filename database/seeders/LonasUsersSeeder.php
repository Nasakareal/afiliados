<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LonasUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissionsSeeder::class);

        $password = env('SEED_LONAS_PASSWORD', 'Lonas2026!');
        $passwordHash = Hash::make($password);

        for ($number = 1; $number <= 30; $number++) {
            $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $email = "lonas{$suffix}@gladyadorez.com";

            $user = User::where('email', $email)->first() ?? new User();
            $user->forceFill([
                'name' => "Captura Lonas {$suffix}",
                'email' => $email,
                'password' => $passwordHash,
                'email_verified_at' => now(),
                'must_change_password' => false,
                'password_changed_at' => now(),
            ])->save();

            $user->syncRoles(['Lonas']);
        }
    }
}
