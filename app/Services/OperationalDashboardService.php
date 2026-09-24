<?php

namespace App\Services;

use App\Models\User;
use App\Support\DemoOperationalKpis;
use App\Support\LocalDistrictAccess;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperationalDashboardService
{
    public function build(User $user): array
    {
        $now = now();
        $affiliates = $this->affiliates($user);
        $activities = $this->activities($user);

        $total = (clone $affiliates)->count();
        $duplicateRows = (int) (clone $affiliates)
            ->whereNotNull('telefono')
            ->where('telefono', '<>', '')
            ->select('telefono', DB::raw('COUNT(*) AS total'))
            ->groupBy('telefono')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->sum(fn ($row) => max(0, (int) $row->total - 1));

        $complete = (int) (clone $affiliates)
            ->where('estatus', 'validado')
            ->whereNotNull('telefono')->where('telefono', '<>', '')
            ->whereNotNull('colonia')->where('colonia', '<>', '')
            ->whereNotNull('seccion')->where('seccion', '<>', '')
            ->count();

        $traceable = (int) (clone $affiliates)
            ->whereNotNull('actividad_id')
            ->whereNotNull('capturista_id')
            ->whereNotNull('colonia')->where('colonia', '<>', '')
            ->count();

        $activityTotal = (clone $activities)->count();
        $teamReports = (clone $activities)->where('origen', 'reporte_usuario');
        $teamReportTotal = (clone $teamReports)->count();
        $officialTotal = (clone $activities)->where('origen', 'oficial')->count();
        $reportedAttendance = (int) ((clone $teamReports)->sum('asistentes') ?? 0);
        $reportsWithEvidence = (clone $teamReports)->where(function ($query): void {
            $query->whereNotNull('evidencia_path')->where('evidencia_path', '<>', '')
                ->orWhere(function ($url): void {
                    $url->whereNotNull('evidencia_url')->where('evidencia_url', '<>', '');
                });
        })->count();
        $participations = DB::table('actividad_participantes')
            ->join('actividades', 'actividades.id', '=', 'actividad_participantes.actividad_id');
        LocalDistrictAccess::scope($participations, 'actividades.distrito_local', $user);
        $participationTotal = $participations->count();
        $activityDone = (clone $activities)->where('estado', 'realizada')->count();
        $activityCancelled = (clone $activities)->where('estado', 'cancelada')->count();
        $coloniesWithActivity = (clone $activities)
            ->where('origen', 'reporte_usuario')
            ->whereNotNull('colonia')->where('colonia', '<>', '')
            ->distinct()->count('colonia');

        $summary = [
            'people' => max(0, $total - $duplicateRows),
            'new_month' => (clone $affiliates)
                ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now])
                ->count(),
            'activities' => $activityTotal,
            'team_reports' => $teamReportTotal,
            'official_activities' => $officialTotal,
            'participations' => $participationTotal,
            'reported_attendance' => $reportedAttendance,
            'evidence_rate' => $this->percent($reportsWithEvidence, $teamReportTotal),
            'colonies' => $coloniesWithActivity,
            'quality' => $this->percent($complete, $total),
            'traceability' => $this->percent($traceable, $total),
            'duplicates' => $duplicateRows,
            'incomplete' => max(0, $total - $complete),
        ];

        $activityKpis = [
            'done' => $activityDone,
            'cancelled' => $activityCancelled,
            'calendar_compliance' => $this->percent($activityDone, max(1, $activityTotal - $activityCancelled)),
            'average_attendance' => round((float) ((clone $activities)->whereNotNull('asistentes')->avg('asistentes') ?? 0), 1),
            'without_evidence' => (clone $activities)->where('estado', 'realizada')
                ->where(function ($query): void {
                    $query->whereNull('evidencia_url')->orWhere('evidencia_url', '');
                })->count(),
            'late_captures' => (clone $activities)->whereNotNull('capturada_en')
                ->whereRaw('capturada_en > inicio')->count(),
        ];

        return [
            'generatedAt' => $now,
            'summary' => $summary,
            'activityKpis' => $activityKpis,
            'daily' => $this->dailySeries($affiliates, $now),
            'responsibles' => $this->responsibleKpis($user),
            'territories' => $this->territories($user, $now),
            'activityReview' => $this->activityReview($user),
            'recentAudit' => $this->recentAudit($user),
            'alerts' => $this->alerts($summary, $activityKpis),
            'hasDemoData' => (clone $affiliates)->where('demo_batch', DemoOperationalKpis::MARKER)->exists()
                || (clone $activities)->where('demo_batch', DemoOperationalKpis::MARKER)->exists(),
        ];
    }

    private function affiliates(User $user): Builder
    {
        $query = DB::table('afiliados')->whereNull('deleted_at');
        return LocalDistrictAccess::scope($query, 'afiliados.distrito_local', $user);
    }

    private function activities(User $user): Builder
    {
        $query = DB::table('actividades');
        return LocalDistrictAccess::scope($query, 'actividades.distrito_local', $user);
    }

    private function dailySeries(Builder $base, Carbon $now): array
    {
        $from = $now->copy()->subDays(13)->startOfDay();
        $rows = (clone $base)
            ->whereBetween('created_at', [$from, $now])
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        $labels = [];
        $values = [];
        for ($date = $from->copy(); $date->lte($now); $date->addDay()) {
            $key = $date->toDateString();
            $labels[] = $date->format('d/m');
            $values[] = (int) ($rows[$key] ?? 0);
        }

        return compact('labels', 'values');
    }

    private function responsibleKpis(User $user): Collection
    {
        $activityRows = $this->activities($user)
            ->whereNotNull('responsable_id')
            ->select('responsable_id')
            ->selectRaw('COUNT(*) AS assigned')
            ->selectRaw("SUM(CASE WHEN estado = 'realizada' THEN 1 ELSE 0 END) AS done")
            ->selectRaw('MAX(inicio) AS last_activity')
            ->groupBy('responsable_id')->get()->keyBy('responsable_id');

        $captureRows = $this->affiliates($user)
            ->select('capturista_id')
            ->selectRaw('COUNT(*) AS captured')
            ->selectRaw("SUM(CASE WHEN estatus = 'validado' THEN 1 ELSE 0 END) AS valid_records")
            ->selectRaw("SUM(CASE WHEN estatus = 'validado' AND telefono IS NOT NULL AND telefono <> '' AND colonia IS NOT NULL AND colonia <> '' AND seccion IS NOT NULL AND seccion <> '' THEN 1 ELSE 0 END) AS complete_records")
            ->selectRaw('COUNT(DISTINCT CASE WHEN colonia IS NOT NULL AND colonia <> \'\' THEN colonia END) AS colonies')
            ->groupBy('capturista_id')->get()->keyBy('capturista_id');

        $duplicateGroups = $this->affiliates($user)
            ->whereNotNull('telefono')->where('telefono', '<>', '')
            ->select('capturista_id', 'telefono')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('capturista_id', 'telefono')
            ->havingRaw('COUNT(*) > 1');
        $duplicates = DB::query()->fromSub($duplicateGroups, 'duplicate_groups')
            ->select('capturista_id')
            ->selectRaw('SUM(total - 1) AS duplicates')
            ->groupBy('capturista_id')->pluck('duplicates', 'capturista_id');

        $ids = $activityRows->keys()->merge($captureRows->keys())->unique()->values();
        $users = DB::table('users')->whereIn('id', $ids)->pluck('name', 'id');

        return $ids->map(function ($id) use ($activityRows, $captureRows, $duplicates, $users) {
            $activity = $activityRows->get($id);
            $capture = $captureRows->get($id);
            $assigned = (int) ($activity->assigned ?? 0);
            $done = (int) ($activity->done ?? 0);
            $captured = (int) ($capture->captured ?? 0);
            $complete = (int) ($capture->complete_records ?? 0);
            $quality = $this->percent($complete, $captured);
            $compliance = $this->percent($done, $assigned);

            return (object) [
                'id' => (int) $id,
                'name' => $users[$id] ?? 'Usuario eliminado',
                'assigned' => $assigned,
                'done' => $done,
                'compliance' => $compliance,
                'captured' => $captured,
                'valid' => (int) ($capture->valid_records ?? 0),
                'duplicates' => (int) ($duplicates[$id] ?? 0),
                'quality' => $quality,
                'colonies' => (int) ($capture->colonies ?? 0),
                'last_activity' => $activity->last_activity ?? null,
                'status' => $quality >= 90 && ($assigned === 0 || $compliance >= 80)
                    ? 'green' : ($quality >= 70 && ($assigned === 0 || $compliance >= 60) ? 'yellow' : 'red'),
            ];
        })->sortByDesc(fn ($row) => [$row->quality, $row->compliance, $row->captured])->values()->take(8);
    }

    private function territories(User $user, Carbon $now): Collection
    {
        $people = $this->affiliates($user)
            ->whereNotNull('colonia')->where('colonia', '<>', '')
            ->select('colonia')
            ->selectRaw('COUNT(*) AS people')
            ->selectRaw("SUM(CASE WHEN estatus = 'validado' THEN 1 ELSE 0 END) AS verified")
            ->selectRaw("SUM(CASE WHEN telefono IS NULL OR telefono = '' OR seccion IS NULL OR seccion = '' THEN 1 ELSE 0 END) AS incomplete")
            ->selectRaw('AVG(lat) AS lat, AVG(lng) AS lng, MAX(created_at) AS last_record')
            ->groupBy('colonia')->get()->keyBy('colonia');

        $activities = $this->activities($user)
            ->whereNotNull('colonia')->where('colonia', '<>', '')
            ->select('colonia')
            ->selectRaw('COUNT(*) AS activities')
            ->selectRaw('COUNT(DISTINCT responsable_id) AS responsibles')
            ->selectRaw('MAX(inicio) AS last_activity')
            ->groupBy('colonia')->get()->keyBy('colonia');

        return $people->keys()->merge($activities->keys())->unique()->map(function ($colony) use ($people, $activities, $now) {
            $p = $people->get($colony);
            $a = $activities->get($colony);
            $last = $a->last_activity ?? $p->last_record ?? null;
            $age = $last ? Carbon::parse($last)->diffInDays($now) : 999;
            return (object) [
                'colony' => $colony,
                'people' => (int) ($p->people ?? 0),
                'verified' => (int) ($p->verified ?? 0),
                'incomplete' => (int) ($p->incomplete ?? 0),
                'activities' => (int) ($a->activities ?? 0),
                'responsibles' => (int) ($a->responsibles ?? 0),
                'last_activity' => $last,
                'lat' => isset($p->lat) ? (float) $p->lat : null,
                'lng' => isset($p->lng) ? (float) $p->lng : null,
                'status' => $age <= 14 ? 'green' : ($age <= 30 ? 'yellow' : 'red'),
            ];
        })->sortByDesc('people')->values()->take(10);
    }

    private function activityReview(User $user): Collection
    {
        return $this->activities($user)
            ->leftJoin('users AS responsible', 'responsible.id', '=', 'actividades.responsable_id')
            ->leftJoin('afiliados', function ($join): void {
                $join->on('afiliados.actividad_id', '=', 'actividades.id')->whereNull('afiliados.deleted_at');
            })
            ->leftJoin('actividad_participantes', 'actividad_participantes.actividad_id', '=', 'actividades.id')
            ->select('actividades.id', 'actividades.titulo', 'actividades.tipo', 'actividades.origen', 'actividades.estado_revision', 'actividades.colonia',
                'actividades.inicio', 'actividades.estado', 'actividades.asistentes',
                'actividades.evidencia_url', 'responsible.name AS responsable')
            ->selectRaw('COUNT(DISTINCT afiliados.id) AS records')
            ->selectRaw("COUNT(DISTINCT CASE WHEN afiliados.estatus = 'validado' THEN afiliados.id END) AS valid_records")
            ->selectRaw('COUNT(DISTINCT actividad_participantes.user_id) AS participants')
            ->groupBy('actividades.id', 'actividades.titulo', 'actividades.tipo', 'actividades.origen', 'actividades.estado_revision', 'actividades.colonia',
                'actividades.inicio', 'actividades.estado', 'actividades.asistentes',
                'actividades.evidencia_url', 'responsible.name')
            ->orderByDesc('actividades.inicio')->limit(8)->get()
            ->map(function ($row) {
                $attendance = (int) ($row->asistentes ?? 0);
                $records = (int) $row->records;
                $ratio = $attendance > 0 ? $records / $attendance : 0;
                $row->anomaly = $row->estado === 'realizada' && (
                    !$row->evidencia_url || ($attendance >= 100 && $records < $attendance * .2) || $ratio > 3
                );
                return $row;
            });
    }

    private function recentAudit(User $user): Collection
    {
        $query = DB::table('auditoria_cambios')
            ->leftJoin('users', 'users.id', '=', 'auditoria_cambios.usuario_id')
            ->select('auditoria_cambios.*', 'users.name AS user_name');

        if (LocalDistrictAccess::restricted($user)) {
            $districts = LocalDistrictAccess::districts($user);
            $query->where(function ($scope) use ($districts): void {
                $scope->where(function ($affiliates) use ($districts): void {
                    $affiliates->where('auditoria_cambios.entidad', 'afiliado')
                        ->whereExists(function ($subquery) use ($districts): void {
                        $subquery->selectRaw('1')->from('afiliados')
                            ->whereColumn('afiliados.id', 'auditoria_cambios.entidad_id')
                            ->whereIn('afiliados.distrito_local', $districts);
                        });
                })->orWhere(function ($activities) use ($districts): void {
                    $activities->where('auditoria_cambios.entidad', 'actividad')
                        ->whereExists(function ($subquery) use ($districts): void {
                            $subquery->selectRaw('1')->from('actividades')
                                ->whereColumn('actividades.id', 'auditoria_cambios.entidad_id')
                                ->whereIn('actividades.distrito_local', $districts);
                        });
                });
            });
        }

        return $query->latest('auditoria_cambios.created_at')->limit(6)->get();
    }

    private function alerts(array $summary, array $activity): array
    {
        return array_values(array_filter([
            $summary['incomplete'] > 0 ? [
                'tone' => 'warning', 'icon' => 'triangle-exclamation',
                'title' => number_format($summary['incomplete']).' registros requieren revisión',
                'text' => 'Les falta teléfono, colonia o sección, o todavía no están validados.',
            ] : null,
            $summary['duplicates'] > 0 ? [
                'tone' => 'danger', 'icon' => 'clone',
                'title' => number_format($summary['duplicates']).' posibles duplicados',
                'text' => 'Se detectaron teléfonos repetidos; conviene validar antes de consolidar.',
            ] : null,
            $activity['without_evidence'] > 0 ? [
                'tone' => 'info', 'icon' => 'camera',
                'title' => number_format($activity['without_evidence']).' actividades sin evidencia',
                'text' => 'Están marcadas como realizadas pero no tienen evidencia vinculada.',
            ] : null,
        ]));
    }

    private function percent(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    }
}
