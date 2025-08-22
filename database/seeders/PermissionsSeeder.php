<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia caché de permisos/roles
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1) Crea todos los permisos
        $perms = [
            // Afiliados (convencidos)
            'afiliados.ver', 'afiliados.crear', 'afiliados.editar', 'afiliados.borrar',

            // Secciones (catálogo + lista nominal)
            'secciones.ver', 'secciones.crear', 'secciones.editar', 'secciones.borrar',

            // Actividades (calendario)
            'actividades.ver', 'actividades.crear', 'actividades.editar', 'actividades.borrar',

            // Mapa y reportes
            'mapa.ver', 'reportes.ver',

            // Settings / administración
            'settings.ver', 'settings.editar',

            // Gestión de usuarios/roles/permisos
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.borrar',
            'roles.ver', 'roles.crear', 'roles.editar', 'roles.borrar',
            'permisos.ver', 'permisos.crear', 'permisos.editar', 'permisos.borrar',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // 2) Crea (o toma) los roles
        $roleSuper = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin',      'guard_name' => 'web']);
        $roleCoord = Role::firstOrCreate(['name' => 'Coordinador','guard_name' => 'web']);
        $roleCapt  = Role::firstOrCreate(['name' => 'Capturista', 'guard_name' => 'web']);
        $roleView  = Role::firstOrCreate(['name' => 'Consulta',   'guard_name' => 'web']);

        // 3) Asignación de permisos por rol
        $roleSuper->syncPermissions(Permission::all());

        // Admin: todo
        $roleAdmin->syncPermissions($perms);

        // Coordinador: operar afiliados/actividades + ver secciones/mapa/reportes
        $roleCoord->syncPermissions([
            'afiliados.ver','afiliados.crear','afiliados.editar','afiliados.borrar',
            'actividades.ver','actividades.crear','actividades.editar','actividades.borrar',
            'secciones.ver','mapa.ver','reportes.ver',
        ]);

        // Capturista: crear/ver afiliados + ver mapa
        $roleCapt->syncPermissions([
            'afiliados.ver','afiliados.crear','mapa.ver',
        ]);

        // Consulta: solo lectura general
        $roleView->syncPermissions([
            'afiliados.ver','secciones.ver','actividades.ver','mapa.ver','reportes.ver',
        ]);

        // Recalcula caché
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
