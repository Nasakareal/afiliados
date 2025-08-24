<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleController extends Controller
{
    public function index()
    {
        $query = Role::query()->where('guard_name', 'web');

        if (!auth()->user()->hasRole('SuperAdmin')) {
            $query->where('name', '!=', 'SuperAdmin');
        }

        $roles = $query->orderBy('name')->paginate(15);

        return view('settings.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('settings.roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('roles','name')->where(fn($q)=>$q->where('guard_name','web')),
            ],
        ]);

        $name = $this->sanitizeRoleName($validated['name']);

        Role::create([
            'name' => $name,
            'guard_name' => 'web',
        ]);

        return redirect()->route('settings.roles.index')
            ->with('status', 'Rol creado correctamente.');
    }

    public function show(Role $role)
    {
        $this->guardRoleAccess($role);
        $this->ensureWebGuard($role);

        $permisos = $role->permissions()->pluck('name')->toArray();

        return view('settings.roles.show', compact('role','permisos'));
    }

    public function edit(Role $role)
    {
        $this->guardRoleAccess($role);
        $this->ensureWebGuard($role);

        return view('settings.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $this->guardRoleAccess($role);
        $this->ensureWebGuard($role);

        $validated = $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('roles','name')
                    ->ignore($role->id)
                    ->where(fn($q)=>$q->where('guard_name','web')),
            ],
        ]);

        $newName = $this->sanitizeRoleName($validated['name']);

        if ($newName === 'SuperAdmin' && !auth()->user()->hasRole('SuperAdmin')) {
            abort(403, 'No puedes asignar/renombrar a SuperAdmin.');
        }

        $role->name = $newName;
        $role->save();

        return redirect()->route('settings.roles.index')
            ->with('status', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role)
    {
        $this->guardRoleAccess($role);
        $this->ensureWebGuard($role);

        if ($role->name === 'SuperAdmin') {
            return back()->withErrors(['delete' => 'No puedes eliminar el rol SuperAdmin.']);
        }

        $usuariosConRol = User::role($role->name)->count();
        if ($usuariosConRol > 0) {
            return back()->withErrors([
                'delete' => "No puedes eliminar el rol porque está asignado a {$usuariosConRol} usuario(s).",
            ]);
        }

        $role->delete();

        return redirect()->route('settings.roles.index')
            ->with('status', 'Rol eliminado correctamente.');
    }

    /* ================= Helpers ================= */

    private function guardRoleAccess(Role $role): void
    {
        if ($role->name === 'SuperAdmin' && !auth()->user()->hasRole('SuperAdmin')) {
            abort(403, 'No autorizado.');
        }
    }

    private function ensureWebGuard(Role $role): void
    {
        if ($role->guard_name !== 'web') {
            abort(404);
        }
    }

    private function sanitizeRoleName(string $name): string
    {
        $name = trim($name);

        if ($name === 'SuperAdmin' && !auth()->user()->hasRole('SuperAdmin')) {
            abort(403, 'No puedes crear/renombrar el rol SuperAdmin.');
        }

        return $name;
    }
}
