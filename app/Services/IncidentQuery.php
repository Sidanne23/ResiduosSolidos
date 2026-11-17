<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncidentQuery
{
    public function build(User $user, array $f): Builder
    {
        return Incident::visibleTo($user)->with(['type', 'state', 'zone', 'assignee'])
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('title', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%")->orWhere('address', 'like', "%{$v}%")))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('detected_at', '>=', CarbonImmutable::parse($v, 'America/Lima')->startOfDay()->utc()))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->where('detected_at', '<=', CarbonImmutable::parse($v, 'America/Lima')->endOfDay()->utc()))
            ->when($f['incident_type_id'] ?? null, fn ($q, $v) => $q->where('incident_type_id', $v))
            ->when($f['status_code'] ?? null, fn ($q, $v) => $q->where('status_code', $v))
            ->when($f['zone_id'] ?? null, fn ($q, $v) => $q->where('zone_id', $v))
            ->when($f['assigned_to'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($f['priority'] ?? null, fn ($q, $v) => $v === 'sin_asignar' ? $q->whereNull('priority') : $q->where('priority', $v))
            ->when($f['verified_only'] ?? false, fn ($q) => $q->whereNotNull('verified_at')->where('status_code', '!=', 'rechazada'))
            ->when(($f['location'] ?? '') === 'verificada', fn ($q) => $q->whereNotNull('location_verified_at'))
            ->when(($f['location'] ?? '') === 'pendiente', fn ($q) => $q->whereNotNull('latitude')->whereNull('location_verified_at'))
            ->when(($f['location'] ?? '') === 'sin_coordenadas', fn ($q) => $q->whereNull('latitude'));
    }

    public function duplicates(Incident $incident, User $user): Collection
    {
        if ($incident->latitude === null || $incident->longitude === null) {
            return collect();
        }
        $setting = DB::table('operational_settings')->where('key', 'duplicate_detection')->value('value');
        $s = json_decode($setting, true);
        $meters = (float) $s['distance_m'];
        $hours = (int) $s['hours'];
        $lat = (float) $incident->latitude;
        $lng = (float) $incident->longitude;
        $dy = $meters / 111320;
        $dx = $meters / (111320 * cos(deg2rad($lat)));

        return Incident::visibleTo($user)->whereKeyNot($incident->id)->whereBetween('latitude', [$lat - $dy, $lat + $dy])->whereBetween('longitude', [$lng - $dx, $lng + $dx])
            ->whereBetween('detected_at', [$incident->detected_at->copy()->subHours($hours), $incident->detected_at->copy()->addHours($hours)])->get()
            ->map(fn ($r) => ['incident' => $r, 'distance' => app(Geography::class)->distance($lat, $lng, (float) $r->latitude, (float) $r->longitude)])
            ->filter(fn ($r) => $r['distance'] <= $meters)->sortBy('distance')->values();
    }
}
