<?php

namespace App\Services;

use App\Models\CriticalSite;
use App\Models\Incident;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\OperationalZone;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OperationalIndicators
{
    public function summarize(User $user, array $filters): array
    {
        $base = app(IncidentQuery::class)->build($user, $filters)->withoutEagerLoads();
        $counts = ['registradas' => (clone $base)->count(), 'abiertas' => Incident::visibleTo($user)->whereNotIn('status_code', ['cerrada', 'rechazada'])->count(),
            'pendientes' => (clone $base)->whereIn('status_code', ['reportada', 'pendiente_verificacion', 'verificada', 'asignada'])->count(),
            'atendidas' => (clone $base)->whereNotNull('attended_at')->count(), 'sin_ubicacion' => (clone $base)->whereNull('latitude')->count(),
            'ubicacion_pendiente' => (clone $base)->whereNotNull('latitude')->whereNull('location_verified_at')->count(), 'sitios_activos' => CriticalSite::where('status', 'activo')->count()];
        $group = function (string $field, $labels) use ($base) {
            return (clone $base)->select($field)->selectRaw('COUNT(*) AS total')->groupBy($field)->get()->map(fn ($row) => ['label' => $labels[$row->$field] ?? 'Zona no declarada', 'value' => (int) $row->total])->all();
        };
        $month = DB::getDriverName() === 'mysql' ? "DATE_FORMAT(DATE_SUB(detected_at, INTERVAL 5 HOUR), '%Y-%m')" : "strftime('%Y-%m', detected_at, '-5 hours')";
        $charts = ['status' => $group('status_code', IncidentStatus::pluck('name', 'code')), 'type' => $group('incident_type_id', IncidentType::pluck('name', 'id')),
            'zone' => $group('zone_id', OperationalZone::pluck('name', 'id')),
            'month' => (clone $base)->selectRaw("{$month} AS month_label, COUNT(*) AS total")->groupByRaw($month)->orderBy('month_label')->get()->map(fn ($row) => ['label' => $row->month_label, 'value' => (int) $row->total])->all()];
        $duration = DB::getDriverName() === 'mysql' ? 'TIMESTAMPDIFF(SECOND, detected_at, attended_at) / 3600.0' : '(julianday(attended_at) - julianday(detected_at)) * 24.0';
        $response = (clone $base)->whereNotNull('verified_at')->whereNotNull('attended_at')->whereColumn('attended_at', '>=', 'detected_at')->selectRaw("COUNT(*) AS cases, AVG({$duration}) AS hours")->first();

        return ['counts' => $counts, 'charts' => $charts, 'responseHours' => $response->hours === null ? null : (float) $response->hours, 'responseCount' => (int) $response->cases];
    }
}
