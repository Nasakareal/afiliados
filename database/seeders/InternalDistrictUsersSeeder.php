<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class InternalDistrictUsersSeeder extends Seeder
{
    /**
     * Provisiona las 24 cuentas de distritos locales para el equipo interno.
     *
     * Los correos usan un prefijo exclusivo para no tocar las cuentas
     * distrito01-distrito24 entregadas previamente al equipo externo.
     */
    public function run(): void
    {
        $lonasRole = Role::findByName('Lonas', 'web');

        $passwordHashes = [
            '$2y$10$bKqii2VhwDopzTWAnG4X2uxR6skASjAVSrQ2SkDnNqW6fG/BprMVa',
            '$2y$10$2CEAwGzGTzJAgV/SUAVMyOAx.gRtzG0H/fIktu23GuZVJdDE9fmIW',
            '$2y$10$rNb2OVfUqBkJJwPZT9d7j.BfTHLtSVCX4uaHRFmTrSSmwAgW4Hena',
            '$2y$10$i/1YbJYcWA/NNqv8IGvVr.gd/jJ/E0f/gB3EKQRPCnYqrI7VW3vye',
            '$2y$10$WzKXCqWTO3KuEj2QoU7tD.RMiQ.sj7X6QphjTT60n3JK3L5zbab1m',
            '$2y$10$ylpzbhFrPlKbGH2eG/d0MuSPACQKX1OEmQKxopKgNOBDvZQ8.ZCyO',
            '$2y$10$./9r8DKW3lMEFKF7admzjeibpCC3EH8BZjAXahXUoVwCwaE4FUkDO',
            '$2y$10$Ifn4MMqurv8Ll2/099mLaOHF/RDvoda4OJcAccLV62kic0OpgA8tO',
            '$2y$10$vuKCWEW/y.DaFY86t.cofuEiC1aSRUa/V8k14kKBY2ms8gRpuciP.',
            '$2y$10$CQyEQUW8UI0LDz1GGm8MyOiAWn/gYqbBBnvPmMyfowA/eQj6ej8iC',
            '$2y$10$djyPG63Ij.LOr24DOtlcluNEvIsr/k4kwNmwQhsV8asb8x6XCxw3m',
            '$2y$10$RMVJVz6NvhghgQVgFsHgiOJ.H9dzc.9UYe8M0edyv29XG4FluyzQO',
            '$2y$10$4/guTFa85e26qPo7c6aNgOwkmRHrVralR2bU/Sc6Pp1SWIm2tNXWi',
            '$2y$10$Zc0qDshcBvmLkV1Kb4rzuuJnNerX5oT3pajytNQb7HxijBDvCIrD2',
            '$2y$10$B2Yrpkle0mTbJP96yCvsQup/tYRe5g.Dq8PNDE0bHmCkSw7jjIcjW',
            '$2y$10$KYyHhiOtuHzvkAhHbTqwXuhMGkRkb2ZGGDwKEYM0TSgynf/khcX1y',
            '$2y$10$JUdOawvhag2fKdExGsc5cuK3BDT50pRN393/lZbiPA1YTzXeaEK32',
            '$2y$10$MDOoM/nKEGDBQ3gGW9ed0uZCqG9NkPQjDVgnozCf/It32aLH/Me4W',
            '$2y$10$Xx5siHu68JcxKgURDJRoAe4ZIvdX/QQz0Gi4QaKYX266elgW2M1Q6',
            '$2y$10$LiM6dna4AUmst7N4Av.LhemUAkzYbo0iX8yK6700oUip7znHVgQ7e',
            '$2y$10$tG84b3j6xzdlyiBjiGsMju7OczQmECGF/svbxGtdQ7tMh0IVopZf2',
            '$2y$10$srejPxgQYzcDPI0p/K/a5.Sz2fY7y1pVuqndBA4SlkkzVaskcYU/S',
            '$2y$10$TYWteSjv2tAsJmo5Cpiv.e/Mqhag7FDUkdbyeKMY13tVHUmmDXwZS',
            '$2y$10$oThRXpr4SMUB46ovXOcA7uZtnnIy5KVD55aBslVguHfyZwBrPSOwK',
        ];

        foreach ($passwordHashes as $index => $passwordHash) {
            $suffix = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $email = "distritointerno{$suffix}@gladyadorez.com";

            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => "Distrito Local Interno {$suffix}",
                'email' => $email,
                'password' => $passwordHash,
                'email_verified_at' => now(),
                'must_change_password' => false,
                'password_changed_at' => now(),
            ])->save();

            $user->syncRoles([$lonasRole]);
        }
    }
}
