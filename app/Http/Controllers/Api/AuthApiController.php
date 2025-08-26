<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
        ]);

        $user = User::where('email',$cred['email'])->first();
        if (!$user || !Hash::check($cred['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciales inválidas']);
        }

        $token = $user->createToken('flutter')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id' => $user->id,
                'name' => $user->name,
                'email'=> $user->email,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['ok' => true]);
    }
}
