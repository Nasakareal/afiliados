<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceApiController extends Controller
{
    public function store(Request $r)
    {
        try {
            $data = $r->validate([
                'token'    => ['required','string'],
                'platform' => ['nullable','string','in:android,ios,other'],
            ]);

            $platform = $data['platform'] ?? 'other';

            $row = DeviceToken::updateOrCreate(
                ['token' => $data['token']],
                [
                    'user_id'      => optional($r->user())->id,
                    'platform'     => $platform,
                    'last_seen_at' => now(),
                ]
            );

            return response()->json(['ok' => true, 'id' => $row->id]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'ok'      => false,
                'message' => 'Datos inválidos',
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error registrando device token', [
                'msg'   => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'body'  => $r->all(),
                'user'  => optional($r->user())->id,
            ]);
            return response()->json([
                'ok'      => false,
                'message' => 'Error del servidor al registrar el dispositivo',
            ], 500);
        }
    }
}
