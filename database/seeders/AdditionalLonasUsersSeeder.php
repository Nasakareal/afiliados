<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdditionalLonasUsersSeeder extends Seeder
{
    /**
     * Provisiona 50 cuentas adicionales sin modificar lonas01-lonas30
     * ni las cuentas de distritos, coordinadores o distritos internos.
     *
     * Ejecutarlo nuevamente restablece solamente las contraseñas de
     * lonas31-lonas80 a las entregadas en el archivo de credenciales.
     */
    public function run(): void
    {
        $this->call(PermissionsSeeder::class);

        $passwordHashes = [
            '$2y$10$d1bywbIw.gQE5RlRybcX5eYQCgWThrGBpkHLRXyZ7ReyIVMY1QrUi',
            '$2y$10$w3gIpL9.SM0jw93Zp2eKE.beYR4jiMvMUgk6tcmlU4lQjTTktGMoG',
            '$2y$10$rVuayooXNWNMDdSBeBK9bOiY0PXKru91jnqm1Sil8eN2vsli9zlci',
            '$2y$10$L3J2yoG7kc11MqGm7o7Y5e9QJi4kPetFxFmSDnZZtdBKn0YUhXHjG',
            '$2y$10$lM1pMf6fC/LjW5JnAYl9UOOOfNuyu1X/BeIvvWp8L3ckwkDykeFla',
            '$2y$10$3M/er6pQBrMCYkxw0MDHx.6PiVwmtRB7d07.Nev7qEEylCrRLsqx6',
            '$2y$10$HE2cxtYvqvqFha8LFY4tS.7HzSUnTWKEBp7t/ZFkWFmTyRunLv6Pi',
            '$2y$10$W7apuj3HZcy9B9ybc.0YauscYxKDlWbEp7GgjmsfKthSCVX7eXxdi',
            '$2y$10$VaEqBt1IRceFjhBVQteLteU4nXU4UdToPvssjOSobTQdbCEES8Zqm',
            '$2y$10$7NR/2skDUFu3t3WOEGTMKOe.GuFw6NyLszIFloK1BbT9Qo7oGBaqi',
            '$2y$10$tiEXHAfKWX/dzSMZlYO.5uCrf9U3yCASmHNnpRpZvK8PlE92UiDOi',
            '$2y$10$VRJ3P0gCPGx81jDLRwrE0.QkT6uEH7Id.d86mMICVQ9Ve7AAMCFJe',
            '$2y$10$ygBG8WxBJetSGWtB0EWtS.TrSam.nV.Fm7ZyQJE8dEtqg.CHeiotu',
            '$2y$10$3qVe5S0jdLuuPNiDHhXsy.JMY7hL6bCqpmF9khLRmhd391bq8JZaG',
            '$2y$10$n2Od9XM6XRDF/hnuoP3hbud/6vNp/uNxmYRi/AqTZlMXcV0t55iPu',
            '$2y$10$FhJ8R8dam5ANXLdUhl0EleLgda4PK9SLgp2mv...Mq7NrlRc6wtW6',
            '$2y$10$iBC0EopcJkvs26B3lVNYc.Jhl17DpSxv6tlIeu6tlbq8CSoXMQyaq',
            '$2y$10$pNHMBk.rRSRAfN3ZHKXdNukirdl4UM1.dK49mnLQzBM/Xq4G01Qq6',
            '$2y$10$2PymeJb5inZZBh9j1iGsOepl38eUSVzO70PCTmeAL0/yOE8nRrt.G',
            '$2y$10$h4FVAvWvspboqvCCRcR9tuwtw/0ztpIR56uMMlxp.c01sLIShf7yi',
            '$2y$10$KSK674dKQ/UT5prlzIdDHOccCVuLg6sUVqTSwTpdSa4FSvoEalnC6',
            '$2y$10$15EFEjV.4l4egXg1QWyWh.p3eWpbSelAWGVcFXKgI3SbzLf1BhASK',
            '$2y$10$LbhbvM4laEhTSiY.SvEio./r14bAS6G4BdsRAkAx07x4oawWit2.e',
            '$2y$10$lVbht.BXwKck4jtRSa.QIOK../eLBFHr16QR9SNp5ah0VNqHqJsjq',
            '$2y$10$l9j13Mw17VLekKQi9gClYuwJklTksnRO9QzmWcNbapTsJXIagGNd6',
            '$2y$10$ymwzgWAwSQc33IoY0q4SqeSf8.Ygj4HPSoLWdE9tko7TowNnQ2G5.',
            '$2y$10$S9RUjIrMwldcfOJ9i.9iEe9mShoXnMQJE/ZW5veC3drsoDojJHC5G',
            '$2y$10$wKS03I3nf0Je7OGNgu9bz.MiQjAB5.Y4ErrAOPxtjxcp7JeqHlhSm',
            '$2y$10$ZSfwjc7mM1sv6baEX2bXuu9LDhnp0iDmDM4tERlyyHnmqMhYQS1T6',
            '$2y$10$QB0AojzpvwaUACs9pKSPq.zB98eeeB8rYpZy69VCNNtgZywf5j44y',
            '$2y$10$fpqVgWQ35BIlFzPxODSnxeSpPSahZPLCUCA/bzsX1ZwkFHMTEZ1Me',
            '$2y$10$Tl/bO2xcUNDrmBFtPe/.Ke173g2MdcEnMRfEsrA7pF3/71woNmN8i',
            '$2y$10$t5VPIr46Zu6jNrFthN99sOcNVeutOpYxWy5.5RhEVyAOVEkIKLlGm',
            '$2y$10$kPvC71tSnm0FAr.wp0ePhOYnS27K222ss2oVm.mVfgYBfrmzMe7la',
            '$2y$10$tAJMUN1e0Zdk0ebg7cCqpeMTIeKGo.q2YvtBnIlbf.RTMJKxT4tvC',
            '$2y$10$sDbeEe2.hXRQLH6atWNhL.AQ4gSOJblRdE65uiQBHl7Me/48drWQq',
            '$2y$10$KE2zfvYKH9xWQtPavuOfvOtiSNVO3HbZ9C3U2irMIZRvR//4fIL/W',
            '$2y$10$R/OAdbLnVbpLLJRYgJlU7OoaC8Td//DGITVy6YmhzbZ4gUstv926S',
            '$2y$10$yJHgevt6xH9wH9.OosQX5.1qpE5B5nWef.DTbOCdQ3FWqeiBJAAwC',
            '$2y$10$0Dsl4Sz5./7dHJFww3spje9wxCzszhvMv5Ht9.ru8q.B/G8pvaF36',
            '$2y$10$RyViv7ouPCjNRs6dy9IKfeOiTTI47D3eg4bSoemWXr8jJMSWqxxn2',
            '$2y$10$kcPgAVbX3HoAQ0SUfy7v7.O/jctAmv74fvcNwN7Zvd/QlvJC.GUpu',
            '$2y$10$rcbanaK6wrU46Q8a2.tZMOuYr5gm/HSE5V67XPCmCGaYBX.oqzH.y',
            '$2y$10$oxPYUUx3DXN4xBpBqH1AvuGadgNzGzlI6D.O9r0ERwFYZ9zfb79TC',
            '$2y$10$OvxR6bMWDjipgYBAsXQO.eiawNexTcgD9531iSBE15QzY3x/9arRa',
            '$2y$10$jyVK/Aq2lktURSVCGxiSbOsOs7f9OJbO4d5arOjHcAJeaaAPEIjdi',
            '$2y$10$61APSvjcAkOCV3H5ild.iuix0RYqIlZclTcnXp9M.QG.EXNuINAVC',
            '$2y$10$bvXh.Qyrq4rBCLyyWAkbb.xUrUQxOxOXAClUXfmj7IAU6njk2pmz.',
            '$2y$10$TCmvQB.LyvbT1910weT.y.8rsvkMwXaW5TwGeIyvsNkS/zf/nFZwm',
            '$2y$10$v0uFAwNg2VlhvRhdaYak0uKY8KvA1r8eiO8nRVZEHYWFb.rvQqBIu',
        ];

        foreach ($passwordHashes as $index => $passwordHash) {
            $accountNumber = $index + 31;
            $suffix = str_pad((string) $accountNumber, 2, '0', STR_PAD_LEFT);
            $email = "lonas{$suffix}@gladyadorez.com";

            $user = User::firstOrNew(['email' => $email]);
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
