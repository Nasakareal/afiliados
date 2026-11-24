<?php

namespace App\Http\Controllers;

use App\Models\Brigada;
use App\Models\Seccion;
use App\Services\GoogleMapsUrlParser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BrigadaController extends Controller
{
    private const MORELIA_CVE_MUN = '053';
    private const MORELIA_DISTRICTS = [10, 11, 16, 17];

    public function index(Request $request)
    {
        $brigadas = $this->filtered($request)
            ->withCount('participantes')
            ->with(['participantes' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->latest()->paginate(20)->withQueryString();

        return view('brigadas.index', compact('brigadas'));
    }

    public function create()
    {
        return view('brigadas.form', ['brigada' => new Brigada(['activo' => true]), 'secciones' => $this->sections()]);
    }

    public function edit(Brigada $brigada)
    {
        $this->ensureMorelia($brigada);
        return view('brigadas.form', ['brigada' => $brigada, 'secciones' => $this->sections()]);
    }

    public function show(Request $request, Brigada $brigada)
    {
        $this->ensureMorelia($brigada);
        $brigada->load('seccion')->loadCount('participantes');
        $miParticipacion = $brigada->participantes()->where('user_id', $request->user()->id)->first();
        $participantes = $request->user()->can('brigadas.participantes.ver')
            ? $brigada->participantes()->with('usuario:id,name')->latest('asistio_en')->get()
            : collect();

        return view('brigadas.show', compact('brigada', 'miParticipacion', 'participantes'));
    }

    public function store(Request $request, GoogleMapsUrlParser $parser)
    {
        $brigada = Brigada::create($this->data($request, $parser) + ['capturado_por' => $request->user()->id]);
        return redirect()->route('brigadas.show', $brigada)->with('success', 'Brigada registrada. Ya pueden confirmar su asistencia.');
    }

    public function update(Request $request, Brigada $brigada, GoogleMapsUrlParser $parser)
    {
        $this->ensureMorelia($brigada);
        $brigada->update($this->data($request, $parser));
        return redirect()->route('brigadas.show', $brigada)->with('success', 'Brigada actualizada.');
    }

    public function destroy(Brigada $brigada)
    {
        $this->ensureMorelia($brigada);
        $brigada->delete();
        return redirect()->route('brigadas.index')->with('success', 'Brigada eliminada.');
    }

    public function export(Request $request)
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Brigadas Morelia');
        $sheet->fromArray(['Sección', 'Distrito local', 'Responsable', 'Teléfono', 'Dirección', 'Asistencias', 'Estado', 'Latitud', 'Longitud', 'Google Maps'], null, 'A1');
        $row = 2;

        $this->filtered($request)->withCount('participantes')->chunkById(500, function ($items) use ($sheet, &$row) {
            foreach ($items as $brigada) {
                $values = [$brigada->seccion->seccion, $brigada->seccion->distrito_local, $brigada->responsable, $brigada->telefono, $brigada->direccion, $brigada->participantes_count, $brigada->activo ? 'Activa' : 'Inactiva', $brigada->lat, $brigada->lng, $brigada->google_maps_url];
                foreach ($values as $index => $value) {
                    $sheet->setCellValueExplicit(chr(65 + $index).$row, (string) $value, DataType::TYPE_STRING);
                }
                $sheet->getCell('J'.$row)->getHyperlink()->setUrl($brigada->google_maps_url);
                $row++;
            }
        });

        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:J'.max(1, $row - 1));

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'brigadas-morelia.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function filtered(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'seccion' => ['nullable', 'string', 'max:6'],
            'distrito' => ['nullable', 'integer', Rule::in(self::MORELIA_DISTRICTS)],
        ]);

        return Brigada::query()->with('seccion')
            ->whereHas('seccion', fn ($query) => $query->where('cve_mun', self::MORELIA_CVE_MUN)->whereIn('distrito_local', self::MORELIA_DISTRICTS))
            ->when($filters['q'] ?? null, fn ($query, $value) => $query->where(fn ($where) => $where->where('responsable', 'like', '%'.$value.'%')->orWhere('direccion', 'like', '%'.$value.'%')))
            ->when($filters['seccion'] ?? null, fn ($query, $value) => $query->whereHas('seccion', fn ($section) => $section->where('seccion', $value)))
            ->when($filters['distrito'] ?? null, fn ($query, $value) => $query->whereHas('seccion', fn ($section) => $section->where('distrito_local', $value)));
    }

    private function sections()
    {
        return Seccion::query()->where('cve_mun', self::MORELIA_CVE_MUN)
            ->whereIn('distrito_local', self::MORELIA_DISTRICTS)
            ->orderBy('distrito_local')->orderByRaw('CAST(seccion AS UNSIGNED)')
            ->get(['id', 'seccion', 'municipio', 'distrito_local']);
    }

    private function data(Request $request, GoogleMapsUrlParser $parser): array
    {
        $data = $request->validate([
            'seccion_id' => ['required', 'integer'], 'responsable' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'], 'direccion' => ['nullable', 'string', 'max:500'],
            'ubicacion_google' => ['nullable', 'string', 'max:2000'], 'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'], 'activo' => ['required', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $validSection = Seccion::query()->whereKey($data['seccion_id'])->where('cve_mun', self::MORELIA_CVE_MUN)->whereIn('distrito_local', self::MORELIA_DISTRICTS)->exists();
        if (!$validSection) {
            throw ValidationException::withMessages(['seccion_id' => 'Selecciona una sección de Morelia.']);
        }

        $url = $parser->normalize($data['ubicacion_google'] ?? null);
        if ($url) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            if (!in_array($host, ['google.com', 'www.google.com', 'maps.google.com', 'google.com.mx', 'www.google.com.mx', 'maps.google.com.mx', 'maps.app.goo.gl', 'goo.gl'], true)) {
                throw ValidationException::withMessages(['ubicacion_google' => 'Introduce un enlace de Google Maps.']);
            }
            if ($point = $parser->coordinates($url)) {
                [$data['lat'], $data['lng']] = $point;
            }
        }
        if (!isset($data['lat'], $data['lng'])) {
            throw ValidationException::withMessages(['lat' => 'Selecciona el punto en el mapa o usa tu ubicación.']);
        }
        $data['ubicacion_google'] = sprintf('https://www.google.com/maps/search/?api=1&query=%s,%s', $data['lat'], $data['lng']);

        return $data;
    }

    private function ensureMorelia(Brigada $brigada): void
    {
        $brigada->loadMissing('seccion');
        abort_unless($brigada->seccion && $brigada->seccion->cve_mun === self::MORELIA_CVE_MUN && in_array((int) $brigada->seccion->distrito_local, self::MORELIA_DISTRICTS, true), 404);
    }
}
