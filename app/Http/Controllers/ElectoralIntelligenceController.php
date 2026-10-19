<?php

namespace App\Http\Controllers;

use App\Services\ElectoralOfferDemandIndex;
use App\Services\SocioeconomicSectionLayer;
use App\Support\LocalDistrictAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ElectoralIntelligenceController extends Controller
{
    public function index(Request $request): View
    {
        $dataset = $this->datasetWithOperation($request);
        $sections = collect($dataset['secciones']);
        $current = $dataset['resumen_municipal']['2024'];

        $quality = [
            'current_sections' => $sections->count(),
            'with_2024_results' => $sections->filter(fn (array $row) => $row['historial']['2024'] !== null)->count(),
            'three_cycle_sections' => $sections->where('elecciones_observadas', 3)->count(),
            'reconfigured_or_new' => $sections->where('elecciones_observadas', '<', 3)->count(),
        ];

        $consistentMorena = $sections
            ->where('morena_gano_todas', true)
            ->sortByDesc(fn (array $row) => $row['historial']['2024']['porcentaje_morena'] ?? 0)
            ->values();
        $indexedSections = $sections
            ->filter(fn (array $row): bool => $row['indice_electoral'] !== null)
            ->sortByDesc(fn (array $row): float => (float) ($row['indice_electoral']['iode'] ?? -INF))
            ->values();

        return view('electoral.index', [
            'metadata' => $dataset['metadata'],
            'summary' => $dataset['resumen_municipal'],
            'current' => $current,
            'quality' => $quality,
            'consistentMorena' => $consistentMorena,
            'operation' => $dataset['operacion'],
            'electoralIndex' => $dataset['indice_electoral'],
            'topIode' => $indexedSections->take(12),
            'socioeconomic' => $dataset['socioeconomico'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()
            ->json($this->datasetWithOperation($request), 200, [], JSON_UNESCAPED_UNICODE)
            ->header('Cache-Control', 'private, no-store');
    }

    private function datasetWithOperation(Request $request): array
    {
        $dataset = app(ElectoralOfferDemandIndex::class)->enrich($this->dataset());
        $dataset = app(SocioeconomicSectionLayer::class)->enrich($dataset);
        $validSections = collect($dataset['secciones'])
            ->pluck('seccion')
            ->map(fn ($section): string => (string) $section)
            ->all();
        $operation = $this->operationBySection($request, $validSections);

        foreach ($dataset['secciones'] as &$section) {
            $section['operacion'] = $operation['secciones'][(string) $section['seccion']] ?? [
                'registrados' => 0,
                'afiliados_si' => 0,
                'afiliados_no' => 0,
            ];
        }
        unset($section);

        $dataset['operacion'] = $operation['resumen'];
        $operationalSections = collect($dataset['secciones']);
        $dataset['operacion']['sin_base_si'] = $operationalSections
            ->filter(fn (array $section) => $section['operacion']['afiliados_si'] === 0)
            ->count();
        $dataset['operacion']['estructura_minima'] = $operationalSections
            ->filter(fn (array $section) => $section['operacion']['afiliados_si'] >= 1 && $section['operacion']['afiliados_si'] <= 9)
            ->count();
        $dataset['operacion']['estructura_fuerte'] = $operationalSections
            ->filter(fn (array $section) => $section['operacion']['afiliados_si'] >= 100)
            ->count();
        $dataset['operacion']['derrotas_margen_corto'] = $operationalSections
            ->filter(function (array $section): bool {
                $result = $section['historial']['2024'];

                return $result !== null
                    && !$result['morena_gano']
                    && abs((int) $result['margen_morena']) <= 100;
            })
            ->count();

        return $dataset;
    }

    private function operationBySection(Request $request, array $validSections): array
    {
        $baseQuery = DB::table('afiliados_resumen as a')
            // La cartografia electoral identifica Morelia como municipio 054,
            // mientras que la base operativa usa la clave municipal 053.
            ->where(function ($municipality): void {
                $municipality
                    ->where('a.cve_mun', '053')
                    ->orWhereRaw('UPPER(TRIM(a.municipio)) = ?', ['MORELIA']);
            });

        LocalDistrictAccess::scope($baseQuery, 'a.distrito_local', $request->user());

        $allRegistered = (int) (clone $baseQuery)->sum('a.total');

        $rows = (clone $baseQuery)
            ->select('a.seccion')
            ->selectRaw('SUM(a.total) AS registrados')
            ->selectRaw("SUM(CASE WHEN a.estatus = 'validado' THEN a.total ELSE 0 END) AS afiliados_si")
            ->selectRaw("SUM(CASE WHEN a.estatus = 'descartado' THEN a.total ELSE 0 END) AS afiliados_no")
            ->whereIn('a.seccion', $validSections)
            ->groupBy('a.seccion')
            ->get()
            ->mapWithKeys(function ($row): array {
                return [(string) ((int) $row->seccion) => [
                    'registrados' => (int) $row->registrados,
                    'afiliados_si' => (int) $row->afiliados_si,
                    'afiliados_no' => (int) $row->afiliados_no,
                ]];
            });

        return [
            'secciones' => $rows->all(),
            'resumen' => [
                'registrados' => (int) $rows->sum('registrados'),
                'afiliados_si' => (int) $rows->sum('afiliados_si'),
                'afiliados_no' => (int) $rows->sum('afiliados_no'),
                'secciones_cubiertas' => $rows->filter(fn (array $row) => $row['afiliados_si'] > 0)->count(),
                'registros_fuera_mapa' => max(0, $allRegistered - (int) $rows->sum('registrados')),
                'tasa_movilizacion_inicial' => 0.75,
                'alcance_restringido' => LocalDistrictAccess::restricted($request->user()),
            ],
        ];
    }

    private function dataset(): array
    {
        $contents = file_get_contents($this->datasetPath());
        $dataset = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return $dataset;
    }

    private function datasetPath(): string
    {
        $path = database_path('data/electoral/morelia_municipal_sections.json');
        abort_unless(is_file($path), 503, 'No se encuentra la base electoral normalizada.');

        return $path;
    }
}
