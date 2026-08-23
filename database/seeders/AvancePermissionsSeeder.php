<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AvancePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $verAvance = Permission::firstOrCreate([
            'name' => 'avance.ver',
            'guard_name' => $guard,
        ]);

        $asignarMetas = Permission::firstOrCreate([
            'name' => 'avance.metas',
            'guard_name' => $guard,
        ]);

        Role::where('guard_name', $guard)
            ->whereIn('name', ['SuperAdmin', 'Admin'])
            ->get()
            ->each(function (Role $role) use ($verAvance, $asignarMetas) {
                $role->givePermissionTo([$verAvance, $asignarMetas]);
            });

        Role::where('guard_name', $guard)
            ->whereIn('name', ['Coordinador', 'Consulta'])
            ->get()
            ->each(function (Role $role) use ($verAvance) {
                $role->givePermissionTo($verAvance);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
