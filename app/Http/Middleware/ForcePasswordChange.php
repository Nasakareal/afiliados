<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            $isOnForceRoute = $request->routeIs('password.force.*');
            $isAuthRoute    = $request->routeIs('login') || $request->routeIs('logout') || $request->routeIs('password.*');

            if ($user->must_change_password && !$isOnForceRoute && !$isAuthRoute) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Debes cambiar tu contraseña.'], 423);
                }
                return redirect()->route('password.force.form')
                    ->with('warning', 'Debes cambiar tu contraseña para continuar.');
            }
        }

        return $next($request);
    }
}
