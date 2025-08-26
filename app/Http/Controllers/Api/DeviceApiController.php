<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceApiController extends Controller
{
    public function store(Request $r)
    {
        $data = $r->validate([
            'token' => ['required','string'],
            'platform' => ['nullable','string','in:android,ios'],
        ]);

        $row = DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => optional($r->user())->id, 'platform' => $data['platform'], 'last_seen_at' => now()]
        );

        return response()->json(['ok' => true, 'id' => $row->id]);
    }
}
