<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Comunicado;
use App\Models\User;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminApiController extends Controller
{
    public function users(Request $request)
    {
        $query = User::with('roles')->orderBy('name');
        LocalDistrictAccess::scope($query);
        if (!$request->user()->hasRole('SuperAdmin')) {
            $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'SuperAdmin'));
        }
        if ($term = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"));
        }

        $page = $query->paginate(min(100, max(1, (int) $request->query('per_page', 25))));
        $page->setCollection($page->getCollection()->map(fn (User $user) => $this->userData($user)));
        return response()->json($page);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'distrito_local' => ['nullable', 'integer', Rule::exists('secciones', 'distrito_local')],
        ]);
        $data = LocalDistrictAccess::force($data, $request->user());
        $this->guardRoleAssignment($request, $data['role']);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'distrito_local' => $data['distrito_local'] ?? null,
        ]);
        $user->forceFill(['must_change_password' => false, 'password_changed_at' => now()])->save();
        $user->syncRoles([$data['role']]);

        return response()->json($this->userData($user), 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $this->guardUserAccess($request, $user);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'distrito_local' => ['nullable', 'integer', Rule::exists('secciones', 'distrito_local')],
        ]);
        $data = LocalDistrictAccess::force($data, $request->user());
        $this->guardRoleAssignment($request, $data['role']);
        if ($user->hasRole('SuperAdmin') && $data['role'] !== 'SuperAdmin' && User::role('SuperAdmin')->count() <= 1) {
            return response()->json(['message' => 'No puedes quitar el último SuperAdmin.'], 422);
        }
        $user->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (array_key_exists('distrito_local', $data)) {
            $user->distrito_local = $data['distrito_local'];
        }
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->password_changed_at = now();
        }
        $user->save();
        \DB::table('actividades')
            ->where('creado_por', $user->id)
            ->update(['distrito_local' => $user->distrito_local]);
        $user->syncRoles([$data['role']]);

        return response()->json($this->userData($user));
    }

    public function destroyUser(Request $request, User $user)
    {
        $this->guardUserAccess($request, $user);
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes eliminar tu propio usuario.'], 422);
        }
        if ($user->hasRole('SuperAdmin') && User::role('SuperAdmin')->count() <= 1) {
            return response()->json(['message' => 'No puedes eliminar al último SuperAdmin.'], 422);
        }
        $user->delete();
        return response()->json(['ok' => true]);
    }

    public function roles(Request $request)
    {
        $query = Role::where('guard_name', 'web')->withCount(['users', 'permissions'])->orderBy('name');
        if (!$request->user()->hasRole('SuperAdmin')) $query->where('name', '!=', 'SuperAdmin');
        return response()->json(['data' => $query->get()]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate(['name' => [
            'required', 'string', 'max:255',
            Rule::unique('roles', 'name')->where('guard_name', 'web'),
        ]]);
        $this->guardRoleAssignment($request, trim($data['name']));
        return response()->json(Role::create(['name' => trim($data['name']), 'guard_name' => 'web']), 201);
    }

    public function updateRole(Request $request, Role $role)
    {
        $this->guardRoleAccess($request, $role);
        $data = $request->validate(['name' => [
            'required', 'string', 'max:255',
            Rule::unique('roles', 'name')->ignore($role->id)->where('guard_name', 'web'),
        ]]);
        $this->guardRoleAssignment($request, trim($data['name']));
        $role->update(['name' => trim($data['name'])]);
        return response()->json($role);
    }

    public function destroyRole(Request $request, Role $role)
    {
        $this->guardRoleAccess($request, $role);
        if ($role->name === 'SuperAdmin') return response()->json(['message' => 'No puedes eliminar SuperAdmin.'], 422);
        if (User::role($role->name)->exists()) return response()->json(['message' => 'El rol está asignado a usuarios.'], 422);
        $role->delete();
        return response()->json(['ok' => true]);
    }

    public function rolePermissions(Request $request, Role $role)
    {
        $this->guardRoleAccess($request, $role);
        return response()->json([
            'role' => $role->only(['id', 'name']),
            'permissions' => Permission::where('guard_name', 'web')->orderBy('name')->pluck('name')->values(),
            'selected' => $role->permissions()->pluck('name')->values(),
        ]);
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        $this->guardRoleAccess($request, $role);
        $data = $request->validate(['permissions' => ['array'], 'permissions.*' => ['string']]);
        $valid = Permission::where('guard_name', 'web')
            ->whereIn('name', $data['permissions'] ?? [])->pluck('name')->all();
        $role->syncPermissions($valid);
        return $this->rolePermissions($request, $role);
    }

    public function comunicados(Request $request)
    {
        $query = Comunicado::with('creador:id,name')->latest();
        if ($term = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('titulo', 'like', "%{$term}%")
                ->orWhere('contenido', 'like', "%{$term}%"));
        }
        return response()->json($query->paginate(min(100, max(1, (int) $request->query('per_page', 25)))));
    }

    public function storeComunicado(Request $request)
    {
        $data = $this->comunicadoData($request);
        $data['creado_por'] = $request->user()->id;
        return response()->json(Comunicado::create($data), 201);
    }

    public function updateComunicado(Request $request, Comunicado $comunicado)
    {
        $comunicado->update($this->comunicadoData($request));
        return response()->json($comunicado);
    }

    public function destroyComunicado(Comunicado $comunicado)
    {
        $comunicado->delete();
        return response()->json(['ok' => true]);
    }

    public function appSettings()
    {
        return response()->json(AppSetting::firstOrDefaults());
    }

    public function updateAppSettings(Request $request)
    {
        $data = $request->validate([
            'captura_habilitada' => ['required', 'boolean'],
            'motivo_bloqueo' => ['nullable', 'string', 'max:1000'],
        ]);
        $setting = AppSetting::query()->first() ?? new AppSetting();
        $setting->fill($data)->save();
        return response()->json($setting);
    }

    private function comunicadoData(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'contenido' => ['required', 'string'],
            'visible_desde' => ['nullable', 'date'],
            'visible_hasta' => ['nullable', 'date', 'after_or_equal:visible_desde'],
            'estado' => ['required', Rule::in(['borrador', 'publicado', 'archivado'])],
        ]);
    }

    private function userData(User $user): array
    {
        $user->load('roles');
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => optional($user->roles->first())->name,
            'roles' => $user->roles->pluck('name')->values(),
            'distrito_local' => $user->distrito_local,
        ];
    }

    private function guardUserAccess(Request $request, User $user): void
    {
        $assigned = LocalDistrictAccess::assigned($request->user());
        if ($assigned !== null && (int)$user->distrito_local !== $assigned) abort(403);
        if ($user->hasRole('SuperAdmin') && !$request->user()->hasRole('SuperAdmin')) abort(403);
    }

    private function guardRoleAccess(Request $request, Role $role): void
    {
        if ($role->guard_name !== 'web') abort(404);
        if ($role->name === 'SuperAdmin' && !$request->user()->hasRole('SuperAdmin')) abort(403);
    }

    private function guardRoleAssignment(Request $request, string $role): void
    {
        if ($role === 'SuperAdmin' && !$request->user()->hasRole('SuperAdmin')) abort(403);
    }
}
