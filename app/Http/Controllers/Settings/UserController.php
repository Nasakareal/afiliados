<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasRole('SuperAdmin')) {
            $usuarios = User::with('roles')
                ->whereDoesntHave('roles', fn($q) => $q->where('name', 'SuperAdmin'))
                ->paginate(15);
        } else {
            $usuarios = User::with('roles')->paginate(15);
        }

        return view('settings.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = auth()->user()->hasRole('SuperAdmin')
            ? Role::all()
            : Role::where('name', '!=', 'SuperAdmin')->get();

        return view('settings.usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|string',
        ]);

        $role = $this->sanitizeRole($validated['role'] ?? null);

        $usuario = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        if (!empty($role)) {
            $usuario->syncRoles([$role]);
        }

        return redirect()->route('settings.usuarios.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    public function show(User $user)
    {
        $this->guardSuper($user);
        return view('settings.usuarios.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->guardSuper($user);

        $roles = auth()->user()->hasRole('SuperAdmin')
            ? Role::all()
            : Role::where('name', '!=', 'SuperAdmin')->get();

        $selectedRole = $user->roles()->pluck('name')->first();

        return view('settings.usuarios.edit', compact('user', 'roles', 'selectedRole'));
    }

    public function update(Request $request, User $user)
    {
        $this->guardSuper($user);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            'role'     => 'required|string',
        ]);

        $newRole = $this->sanitizeRole($validated['role'] ?? null);
        $oldIsSuper = $user->hasRole('SuperAdmin');
        $newIsSuper = ($newRole === 'SuperAdmin');

        if ($oldIsSuper && !$newIsSuper) {
            $superAdmins = User::role('SuperAdmin')->count();
            if ($superAdmins <= 1) {
                return back()
                    ->withErrors(['role' => 'No puedes quitar el último SuperAdmin del sistema.'])
                    ->withInput();
            }
        }

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        $user->save();

        $user->syncRoles([$newRole]);

        return redirect()->route('settings.usuarios.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $this->guardSuper($user);

        if ($user->hasRole('SuperAdmin')) {
            $superAdmins = User::role('SuperAdmin')->count();
            if ($superAdmins <= 1) {
                return back()->withErrors(['delete' => 'No puedes eliminar al último SuperAdmin del sistema.']);
            }
        }

        $user->delete();

        return redirect()->route('settings.usuarios.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }

    /* ================== Helpers de seguridad ================== */

    private function guardSuper(User $user): void
    {
        if ($user->hasRole('SuperAdmin') && !auth()->user()->hasRole('SuperAdmin')) {
            abort(403, 'No autorizado.');
        }
    }

    private function sanitizeRole(?string $role): ?string
    {
        if (!$role) return null;

        if (!auth()->user()->hasRole('SuperAdmin') && $role === 'SuperAdmin') {
            abort(403, 'No puedes asignar el rol SuperAdmin.');
        }

        return $role;
    }
}
