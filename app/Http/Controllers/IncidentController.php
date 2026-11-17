<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentEvidenceRequest;
use App\Http\Requests\IncidentFilterRequest;
use App\Http\Requests\IncidentRequest;
use App\Http\Requests\IncidentTransitionRequest;
use App\Models\CriticalSite;
use App\Models\Incident;
use App\Models\IncidentEvidence;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\OperationalZone;
use App\Models\User;
use App\Services\Geography;
use App\Services\IncidentQuery;
use App\Services\IncidentWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class IncidentController extends Controller
{
    public function index(IncidentFilterRequest $request, IncidentQuery $query)
    {
        $filters = $request->validated();
        $base = $query->build($request->user(), $filters);

        return view('incidents.index', $this->catalogs() + ['filters' => $filters, 'incidents' => $base->latest('detected_at')->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        Gate::authorize('create', Incident::class);

        return view('incidents.form', $this->catalogs() + ['incident' => new Incident, 'geography' => app(Geography::class)->feature()]);
    }

    public function store(IncidentRequest $request, IncidentWorkflow $workflow)
    {
        $incident = $workflow->create($request->validated(), $request->file('photos', []), $request->user());

        return redirect()->route('incidents.show', $incident)->with('status', 'Incidencia registrada. Está pendiente de verificación; seleccionar coordenadas no la verifica automáticamente.');
    }

    public function show(Incident $incident, IncidentQuery $query)
    {
        Gate::authorize('view', $incident);
        $incident->load(['type', 'state', 'zone', 'reporter', 'assignee', 'verifier', 'evidence.uploader', 'history.user', 'site', 'duplicateOf']);
        $codes = DB::table('incident_status_transitions')->where(['from_status' => $incident->status_code, 'enabled' => true])->pluck('to_status');
        if (! auth()->user()->can('manage', Incident::class)) {
            $codes = $codes->intersect(['pendiente_verificacion']);
        } else {
            $codes = $codes->prepend($incident->status_code)->unique();
        }

        return view('incidents.show', $this->catalogs() + ['incident' => $incident, 'transitions' => IncidentStatus::whereIn('code', $codes)->orderBy('position')->get(),
            'duplicates' => $query->duplicates($incident, auth()->user()), 'sites' => CriticalSite::orderBy('name')->get(), 'geography' => app(Geography::class)->feature()]);
    }

    public function edit(Incident $incident)
    {
        Gate::authorize('update', $incident);

        return view('incidents.form', $this->catalogs() + ['incident' => $incident, 'geography' => app(Geography::class)->feature()]);
    }

    public function update(IncidentRequest $request, Incident $incident, IncidentWorkflow $workflow)
    {
        $workflow->update($incident, $request->validated(), $request->file('photos', []), $request->user());

        return redirect()->route('incidents.show', $incident)->with('status', 'Reporte actualizado con historial. Los cambios sustantivos requieren nueva verificación.');
    }

    public function transition(IncidentTransitionRequest $request, Incident $incident, IncidentWorkflow $workflow)
    {
        $workflow->transition($incident, $request->validated(), $request->user());

        return back()->with('status', 'Movimiento registrado. El historial anterior se conserva.');
    }

    public function attach(IncidentEvidenceRequest $request, Incident $incident, IncidentWorkflow $workflow)
    {
        $workflow->attach($incident, $request->file('photos'), $request->validated('phase'), $request->validated('caption'), (int) $request->validated('lock_version'), $request->user());

        return back()->with('status', 'Fotografías incorporadas al expediente.');
    }

    public function photo(IncidentEvidence $evidence)
    {
        Gate::authorize('view', $evidence->incident);
        abort_unless(Storage::disk('local')->exists($evidence->private_path), 404);

        return response()->file(Storage::disk('local')->path($evidence->private_path), ['Content-Type' => $evidence->mime, 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox", 'Cache-Control' => 'private, no-store']);
    }

    public function map(IncidentFilterRequest $request)
    {
        $filters = $request->validated() + ['verified_only' => 0];

        return view('incidents.map', $this->catalogs() + compact('filters') + ['geography' => app(Geography::class)->feature()]);
    }

    public function mapData(IncidentFilterRequest $request, IncidentQuery $query)
    {
        $filters = $request->validated() + ['verified_only' => 0];
        $base = $query->build($request->user(), $filters);
        $total = (clone $base)->count();
        $located = (clone $base)->whereNotNull('latitude')->whereNotNull('longitude');
        abort_if((clone $located)->count() > 10000, 422, 'Seleccione un período o zona más específico para cargar el mapa.');
        $items = $located->get();
        $features = $items->map(fn ($r) => ['type' => 'Feature', 'geometry' => ['type' => 'Point', 'coordinates' => [(float) $r->longitude, (float) $r->latitude]], 'properties' => [
            'id' => $r->id, 'code' => $r->code, 'title' => $r->title, 'address' => $r->address, 'type' => $r->type->name, 'state' => $r->state->name,
            'priority' => $r->priority ?? 'Sin asignar', 'zone' => $r->zone?->name ?? 'Sin zona declarada', 'detected_at' => $r->detected_local,
            'verified' => $r->verified_at !== null && $r->status_code !== 'rechazada', 'location_verified' => $r->location_verified_at !== null,
            'url' => route('incidents.show', $r), 'critical_site_id' => $r->critical_site_id]]);
        $verified = $items->filter(fn ($r) => $r->verified_at !== null && $r->location_verified_at !== null && $r->status_code !== 'rechazada');

        return response()->json(['type' => 'FeatureCollection', 'features' => $features->values(),
            'meta' => ['reports' => $total, 'geolocated' => $items->count(), 'verified_geolocated' => $verified->count(), 'without_coordinates' => $total - $items->count(),
                'unique_sites' => $verified->pluck('critical_site_id')->filter()->unique()->count(),
                'message' => $items->isEmpty() ? 'No hay incidencias con coordenadas para los filtros seleccionados.' : ($verified->isEmpty() ? 'Los marcadores muestran reportes pendientes de verificación. La capa de calor todavía no tiene reportes verificados.' : null),
                'scope' => 'La intensidad representa densidad de reportes verificados, no toneladas ni riesgo ambiental. Cada reporte aporta peso 1.', 'updated_at' => now()->toIso8601String()]]);
    }

    private function catalogs(): array
    {
        return ['types' => IncidentType::orderBy('name')->get(), 'zones' => OperationalZone::orderBy('name')->get(), 'states' => IncidentStatus::orderBy('position')->get(),
            'responsibles' => User::where('active', true)->whereIn('role', ['administrador', 'analista', 'registrador', 'responsable_limpieza'])->orderBy('name')->get()];
    }
}
