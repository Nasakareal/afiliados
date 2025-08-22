<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['SuperAdmin','Admin','Coordinador','Capturista','Consulta'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
    }
}
