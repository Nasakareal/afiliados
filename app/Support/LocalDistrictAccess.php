<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LocalDistrictAccess
{
    public static function assigned(?User $user = null): ?int
    {
        return self::districts($user)[0] ?? null;
    }

    public static function districts(?User $user = null): array
    {
        if (!$user) {
            try {
                $user = Auth::user();
            } catch (\Throwable $error) {
                return [];
            }
        }

        if (!$user) {
            return [];
        }

        if (Schema::hasTable('user_local_districts')) {
            return $user->localDistrictNumbers();
        }

        return $user->distrito_local !== null ? [(int) $user->distrito_local] : [];
    }

    public static function restricted(?User $user = null): bool
    {
        return self::districts($user) !== [];
    }

    public static function scope($query, string $column = 'distrito_local', ?User $user = null)
    {
        $districts = self::districts($user);

        if ($districts) {
            $query->whereIn($column, $districts);
        }

        return $query;
    }

    public static function authorize($district, ?User $user = null): void
    {
        $assigned = self::districts($user);

        if ($assigned && !in_array((int) $district, $assigned, true)) {
            abort(403, 'No tienes acceso a ese distrito local.');
        }
    }

    public static function force(array $data, ?User $user = null): array
    {
        $assigned = self::districts($user);

        if (count($assigned) === 1) {
            $data['distrito_local'] = $assigned[0];
        } elseif ($assigned) {
            if (isset($data['distrito_local'])) {
                self::authorize($data['distrito_local'], $user);
            } else {
                // Los registros sin sección (por ejemplo actividades) conservan
                // un distrito determinista para no desaparecer del alcance del creador.
                $data['distrito_local'] = $assigned[0];
            }
        }

        return $data;
    }

    public static function sectionIsAllowed(string $section, ?User $user = null): bool
    {
        $assigned = self::districts($user);
        if (!$assigned) {
            return true;
        }

        return \DB::table('secciones')
            ->where('seccion', $section)
            ->whereIn('distrito_local', $assigned)
            ->exists();
    }
}
