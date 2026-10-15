<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSystemAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->canAccessSystem() || $request->routeIs('api.auth.logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => User::ACCESS_RESTRICTION_MESSAGE,
            ], 403);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => User::ACCESS_RESTRICTION_MESSAGE,
        ]);
    }
}
