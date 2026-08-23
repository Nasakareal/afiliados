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
        // Guard a usar (debe coincidir con tu auth/guards)
        $guard = 'web';

        // Limpia la caché de permisos/roles
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1) Definir todos los permisos del sistema
        $perms = [

            // Comunicados
            'comunicados.ver', 'comunicados.crear', 'comunicados.editar', 'comunicados.borrar',

            // Afiliados (convencidos)
            'afiliados.ver', 'afiliados.crear', 'afiliados.editar', 'afiliados.borrar',

            // Secciones (catálogo + lista nominal)
            'secciones.ver', 'secciones.crear', 'secciones.editar', 'secciones.borrar',

            // Actividades (calendario)
            'actividades.ver', 'actividades.crear', 'actividades.editar', 'actividades.borrar',

            // Captura y mapa de lonas
            'lonas.ver', 'lonas.crear', 'lonas.editar', 'lonas.borrar',

            // Mapa y reportes
            'mapa.ver', 'reportes.ver',

            // Avance y asignación de metas
            'avance.ver', 'avance.metas',

            // Settings / administración
            'settings.ver', 'settings.editar',

            // Gestión de usuarios/roles/permisos
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.borrar',
            'roles.ver', 'roles.crear', 'roles.editar', 'roles.borrar',
            'permisos.ver', 'permisos.crear', 'permisos.editar', 'permisos.borrar',
        ];

        // 2) Crear (si no existen) todos los permisos con el guard correcto
        foreach ($perms as $p) {
            Permission::firstOrCreate([
                'name'       => $p,
                'guard_name' => $guard,
            ]);
        }

        // 3) Crear (o tomar) los roles con el mismo guard
        $roleSuper = Role::firstOrCreate(['name' => 'SuperAdmin',  'guard_name' => $guard]);
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin',       'guard_name' => $guard]);
        $roleCoord = Role::firstOrCreate(['name' => 'Coordinador', 'guard_name' => $guard]);
        $roleCapt  = Role::firstOrCreate(['name' => 'Capturista',  'guard_name' => $guard]);
        $roleView  = Role::firstOrCreate(['name' => 'Consulta',    'guard_name' => $guard]);
        $roleLonas = Role::firstOrCreate(['name' => 'Lonas',       'guard_name' => $guard]);

        // 4) Asignación de permisos por rol

        // SuperAdmin: siempre todos los permisos existentes en la BD (según guard)
        $roleSuper->syncPermissions(
            Permission::where('guard_name', $guard)->get()
        );

        // Admin: agregar (no quitar) los permisos listados en $perms
        $roleAdmin->givePermissionTo($perms);

        // Coordinador: operar afiliados/actividades + ver secciones/mapa/reportes (aditivo)
        $roleCoord->givePermissionTo([
            'afiliados.ver','afiliados.crear','afiliados.editar','afiliados.borrar',
            'actividades.ver','actividades.crear','actividades.editar','actividades.borrar',
            'secciones.ver','mapa.ver','reportes.ver',
            'avance.ver',
            'lonas.ver','lonas.crear','lonas.editar','lonas.borrar',
        ]);

        // Capturista: crear/ver afiliados + ver mapa (aditivo)
        $roleCapt->givePermissionTo([
            'afiliados.ver','afiliados.crear','mapa.ver',
            'lonas.ver','lonas.crear',
        ]);

        // Consulta: solo lectura general (aditivo)
        $roleView->givePermissionTo([
            'afiliados.ver','secciones.ver','actividades.ver','mapa.ver','reportes.ver',
            'lonas.ver','avance.ver',
        ]);

        // Usuarios genéricos de lonas: únicamente este módulo (captura, listado y mapa).
        $roleLonas->syncPermissions([
            'lonas.ver','lonas.crear',
        ]);

        // Recalcula/limpia de nuevo la caché
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
