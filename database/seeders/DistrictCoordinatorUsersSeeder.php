<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DistrictCoordinatorUsersSeeder extends Seeder
{
    /**
     * Provisiona las 30 cuentas entregadas en el archivo de credenciales.
     * Ejecutarlo nuevamente restablece las 30 contraseñas a las entregadas.
     */
    public function run(): void
    {
        $this->call(PermissionsSeeder::class);

        $passwordHashes = [
            '$2y$10$KmTU2dDDqixU8.HPDRfBbux.MrC4rfsWWZjaOYgJIaf1Y7ZU8gZVW',
            '$2y$10$lm8y11H3pXWkeoztF1/dVedluycZij1QdfpzsYXMP32kLa6kajxPa',
            '$2y$10$k2q9yHUOpKiqXGQ4icNBQuAlYJaVMlndK6NPdnIpeHpj10T5At.9e',
            '$2y$10$ndkgj0TFXxv0Zdg/k6UYhOD3LP.C9hIX...tePpW3VY2U0.O/YdFy',
            '$2y$10$GN9bnvrtE/NweDuexcJeVuwW1RrUIBtI102j6XsEBDu8aEIYXoIdi',
            '$2y$10$LvxoVOzZ/pJ4oyXzCj9VDe4NrkAz6hQ7kA/Dk2o3eH3vpKuunwBQW',
            '$2y$10$b/P.npRBlN8sJzW5lK1HD.9OHdNW7QVFFEeUt51GvXSWDzjOPDjUO',
            '$2y$10$0CdAwPR3Gjs9IT4rpDzJ7eTHwMPQ58TC.MtyGAkoFTNkmPXKNeaqK',
            '$2y$10$86FPO1dH28ZoZr3z0K/1tOWPn3KEbqYYB8Z0E3r47cDchwwWypu.m',
            '$2y$10$YPnLX0XSHRTFTnkYSUb0AOuZIHzCakLe2l1K0XQGXbuREpk7/VsVS',
            '$2y$10$aL1Ti9CVXtbDXH4l1LULH.Pxdgo4pXJ6eLpEgkleYQnCPZfyl63W.',
            '$2y$10$Osa6TS9pCy236GV8zS7WXOeP1zpU6Mdq8a/bVvsRS4WQ0Sndbusxe',
            '$2y$10$uCl6ICqrnlg8UZtwi8FWCunyNct/1EpS8eQbaEvEnQlyFurB9Anle',
            '$2y$10$CpNTI0kYt.r7ARc01G3c1uJL2CZL5Om1QSHtpCulNpt3sdZuk0ioK',
            '$2y$10$9kA85scVA41yJjW3NyGgEust3q04i/HAbV7eXQsgYukXvBd89uBXe',
            '$2y$10$AeiV9/ByiXOf7tBXV0g1S.bT1dSBmG6J9IKu6eIbP9/LmKN7Aom9e',
            '$2y$10$9jPg5ZVdRTrkfMdRfQyrl.V3NTTv3B2MODu0hyRxGiBYa7JbOik52',
            '$2y$10$2ssXa.2cF623..DzlxEGo.jmNFEh6DKcAJiUZiI4KSQzcCdG62Ui6',
            '$2y$10$4SxjGxXvUp0HK2PCxLOtX.kA.Wjy8QrYMGrM.noTXY44TEcmhmcDO',
            '$2y$10$U44QuwpO3AAWch7EQs/zfuEatzsf3V0OP2YEJUivme/WngN0/lExG',
            '$2y$10$C0KY/zhAIvB.jnSGlPFyIuLQI9D.1h/JP1F39uOSMxSY8BkQ6Bkiu',
            '$2y$10$fM2xFCOiN7tBVitjn1ycW.YtBQIb7yMhLtii/rM897z3em0e0cS.i',
            '$2y$10$VREX/TQj8N/9d6tsreWzjOqfydA7ln5ht1wuSqTT7g94ubvMF9Ese',
            '$2y$10$FWmrzAWUGG.rHVwP0uwpCOfvq6T/Zi7WKANEEBl56Q5tq5uTgA87u',
            '$2y$10$iI0k52GKZzDjOFZDkMpxyOdiSu616boJNOjwytH.2V50BRqDNisHa',
            '$2y$10$iEYc7F5QRalOQj0nfR4d0uezhpxKH3XT0ilnedfpsyVpN7ISOuYUm',
            '$2y$10$RS/pDc7f9p9PLklpRbUxNOISaYAM.jS7xKGN/ScuZG/YicyhomjWG',
            '$2y$10$EYqi1FlUxJsQszbPPZ3vOuofddeTQkFLz8EQwbHKgCjplD1QDQJwy',
            '$2y$10$nhcpfdMbN0JXRlmchtnE4.UvbeT.09ttbaSMj1P8FA2BaStKwVVjy',
            '$2y$10$QswcrlDIN95Gh1kMFv.xSORK9I9/ngtSR9Z97XyMSqL4o2hfsr6t6',
        ];

        foreach ($passwordHashes as $index => $passwordHash) {
            $accountNumber = $index + 1;

            if ($accountNumber <= 24) {
                $suffix = str_pad((string) $accountNumber, 2, '0', STR_PAD_LEFT);
                $name = "Distrito Local {$suffix}";
                $email = "distrito{$suffix}@gladyadorez.com";
            } else {
                $coordinatorNumber = $accountNumber - 24;
                $suffix = str_pad((string) $coordinatorNumber, 2, '0', STR_PAD_LEFT);
                $name = "Coordinador {$suffix}";
                $email = "coordinador{$suffix}@gladyadorez.com";
            }

            $user = User::where('email', $email)->first() ?? new User();
            $user->forceFill([
                'name' => $name,
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
