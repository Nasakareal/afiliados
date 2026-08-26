<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LocalDistrictAccess
{
    public static function assigned(?User $user = null): ?int
    {
        if (!$user) {
            try {
                $user = Auth::user();
            } catch (\Throwable $error) {
                return null;
            }
        }

        return $user && $user->distrito_local !== null
            ? (int)$user->distrito_local
            : null;
    }

    public static function scope($query, string $column = 'distrito_local', ?User $user = null)
    {
        $district = self::assigned($user);

        if ($district !== null) {
            $query->where($column, $district);
        }

        return $query;
    }

    public static function authorize($district, ?User $user = null): void
    {
        $assigned = self::assigned($user);

        if ($assigned !== null && (int)$district !== $assigned) {
            abort(403, 'No tienes acceso a ese distrito local.');
        }
    }

    public static function force(array $data, ?User $user = null): array
    {
        $assigned = self::assigned($user);

        if ($assigned !== null) {
            $data['distrito_local'] = $assigned;
        }

        return $data;
    }

    public static function sectionIsAllowed(string $section, ?User $user = null): bool
    {
        $assigned = self::assigned($user);
        if ($assigned === null) {
            return true;
        }

        return \DB::table('secciones')
            ->where('seccion', $section)
            ->where('distrito_local', $assigned)
            ->exists();
    }
}
