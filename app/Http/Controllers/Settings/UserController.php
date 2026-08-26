<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $distritoAsignado = LocalDistrictAccess::assigned(auth()->user());
        $query = User::with('roles')
            ->when($distritoAsignado !== null, fn($q) => $q->where('distrito_local', $distritoAsignado));

        if (!auth()->user()->hasRole('SuperAdmin')) {
            $query->whereDoesntHave('roles', fn($q) => $q->where('name', 'SuperAdmin'));
        }

        $usuarios = $query->paginate(15);

        return view('settings.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = auth()->user()->hasRole('SuperAdmin')
            ? Role::all()
            : Role::where('name', '!=', 'SuperAdmin')->get();

        $distritosLocales = $this->distritosLocales();

        return view('settings.usuarios.create', compact('roles', 'distritosLocales'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|string',
            'distrito_local' => [
                'nullable',
                'integer',
                Rule::exists('secciones', 'distrito_local'),
            ],
        ]);
        $validated = LocalDistrictAccess::force($validated, $request->user());

        $role = $this->sanitizeRole($validated['role'] ?? null);

        $usuario = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
            'distrito_local' => $validated['distrito_local'] ?? null,
        ]);
        $usuario->forceFill([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        if (!empty($role)) {
            $usuario->syncRoles([$role]);
        }

        return redirect()->route('settings.usuarios.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    public function show(User $user)
    {
        $this->guardDistrict($user);
        $this->guardSuper($user);
        return view('settings.usuarios.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->guardDistrict($user);
        $this->guardSuper($user);

        $roles = auth()->user()->hasRole('SuperAdmin')
            ? Role::all()
            : Role::where('name', '!=', 'SuperAdmin')->get();

        $selectedRole = $user->roles()->pluck('name')->first();
        $distritosLocales = $this->distritosLocales();

        return view('settings.usuarios.edit', compact('user', 'roles', 'selectedRole', 'distritosLocales'));
    }

    public function update(Request $request, User $user)
    {
        $this->guardDistrict($user);
        $this->guardSuper($user);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            'role'     => 'required|string',
            'distrito_local' => [
                'nullable',
                'integer',
                Rule::exists('secciones', 'distrito_local'),
            ],
        ]);
        $validated = LocalDistrictAccess::force($validated, $request->user());

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
        $user->distrito_local = $validated['distrito_local'] ?? null;
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
            $user->must_change_password = false;
            $user->password_changed_at = now();
        }
        $user->save();
        DB::table('actividades')
            ->where('creado_por', $user->id)
            ->update(['distrito_local' => $user->distrito_local]);

        $user->syncRoles([$newRole]);

        return redirect()->route('settings.usuarios.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $this->guardDistrict($user);
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

    private function guardDistrict(User $user): void
    {
        $assigned = LocalDistrictAccess::assigned(auth()->user());

        if ($assigned !== null && (int)$user->distrito_local !== $assigned) {
            abort(403, 'No tienes acceso a usuarios de otro distrito local.');
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

    private function distritosLocales()
    {
        $query = DB::table('secciones')
            ->whereNotNull('distrito_local')
            ->distinct()
            ->orderBy('distrito_local');
        LocalDistrictAccess::scope($query);

        return $query->pluck('distrito_local');
    }
}
