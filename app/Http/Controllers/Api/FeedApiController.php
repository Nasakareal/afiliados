<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lona;
use Illuminate\Http\Request;

class FeedApiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(12, max(4, (int) $request->query('per_page', 8)));
        $cursor = max(0, (int) $request->query('cursor', 0));

        $rows = Lona::with('capturista:id,name')
            ->when($cursor > 0, fn ($query) => $query->where('id', '<', $cursor))
            ->orderByDesc('id')
            ->limit($perPage + 1)
            ->get();

        $hasMore = $rows->count() > $perPage;
        $pageRows = $rows->take($perPage)->values();
        $items = $pageRows->map(function (Lona $item) {
            return [
                'type' => 'lona',
                'id' => (int) $item->id,
                'author' => optional($item->capturista)->name ?: 'Equipo territorial',
                'title' => 'Colocó una lona en la sección ' . $item->seccion,
                'body' => trim((string) $item->direccion),
                'meta' => trim((string) $item->responsable),
                'image_url' => route('api.lonas.photo', $item) . '?variant=feed',
                'created_at' => optional($item->created_at)->toIso8601String(),
                'route' => '/lonas',
            ];
        });

        return response()->json([
            'items' => $items,
            'has_more' => $hasMore,
            'next_cursor' => $hasMore && $pageRows->isNotEmpty()
                ? (string) $pageRows->last()->id
                : null,
        ]);
    }
}
