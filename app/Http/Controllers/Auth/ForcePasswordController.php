<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcePasswordController extends Controller
{
    /** GET /password/force */
    public function form()
    {
        return view('auth.force-password');
    }

    /** POST /password/force */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
        ]);

        $user = $request->user();

        $user->forceFill([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
            'password_changed_at'  => now(),
        ])->save();

        return redirect()->intended('/')->with('success', 'Contraseña actualizada correctamente.');
    }
}
