<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    public function show($id)
    {
        $role = Role::where('guard_name','web')->findOrFail($id);
        if ($role->name === 'SuperAdmin' && !auth()->user()->hasRole('SuperAdmin')) abort(403);
        return view('settings.roles.permissions.show', compact('role'));
    }

    public function edit($id)
    {
        $role = Role::where('guard_name','web')->findOrFail($id);
        if ($role->name === 'SuperAdmin' && !auth()->user()->hasRole('SuperAdmin')) abort(403);

        $permissions   = Permission::where('guard_name','web')->orderBy('name')->get();
        $rolePermNames = $role->permissions()->pluck('name')->toArray();

        return view('settings.roles.permissions.edit', compact('role','permissions','rolePermNames'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::where('guard_name','web')->findOrFail($id);
        if ($role->name === 'SuperAdmin' && !auth()->user()->hasRole('SuperAdmin')) abort(403);

        $data = $request->validate([
            'permissions'   => ['array'],
            'permissions.*' => ['string'],
        ]);

        $valid = Permission::where('guard_name','web')
            ->whereIn('name', $data['permissions'] ?? [])
            ->pluck('name')->toArray();

        $role->syncPermissions($valid);

        return redirect()->route('settings.roles.permisos.show', $role->id)
            ->with('status','Permisos actualizados correctamente.');
    }
}
