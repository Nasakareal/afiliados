<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BrigadasPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'brigadas.ver', 'brigadas.crear', 'brigadas.editar', 'brigadas.borrar',
            'brigadas.participar', 'brigadas.participantes.ver',
        ];
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (Role::whereIn('name', ['Admin', 'SuperAdmin'])->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo($permissions);
        }
        foreach (Role::whereIn('name', ['Capturista', 'Consulta', 'Lonas'])->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo(['brigadas.ver', 'brigadas.participar']);
        }
        if ($role = Role::where('name', 'Coordinador')->where('guard_name', 'web')->first()) {
            $role->givePermissionTo(['brigadas.ver', 'brigadas.crear', 'brigadas.editar', 'brigadas.participar', 'brigadas.participantes.ver']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
