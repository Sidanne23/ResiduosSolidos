<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriticalSiteRequest;
use App\Models\CriticalSite;
use App\Models\Incident;
use App\Services\Audit;
use App\Services\Geography;
use App\Services\IncidentWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CriticalSiteController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['activo', 'atendido', 'cerrado'])], 'q' => ['nullable', 'string', 'max:150']]);
        $sites = CriticalSite::with('zone')->withCount(['incidents' => fn ($q) => $q->visibleTo($request->user())])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))->latest()->paginate(15)->withQueryString();

        return view('incidents.sites', compact('sites', 'filters'));
    }

    public function show(CriticalSite $site)
    {
        $site->load('zone', 'verifier');

        return view('incidents.site', ['site' => $site, 'incidents' => $site->incidents()->visibleTo(auth()->user())->with('state')->latest()->get(),
            'history' => DB::table('critical_site_history')->join('users', 'users.id', '=', 'critical_site_history.user_id')->where('critical_site_id', $site->id)->select('critical_site_history.*', 'users.name as user_name')->orderByDesc('critical_site_history.id')->get(), 'geography' => app(Geography::class)->feature()]);
    }

    public function store(CriticalSiteRequest $request, Incident $incident, IncidentWorkflow $workflow)
    {
        Gate::authorize('manage', Incident::class);
        $data = $request->validated();
        $site = DB::transaction(function () use ($incident, $data, $workflow, $request) {
            $row = Incident::lockForUpdate()->findOrFail($incident->id);
            if ($row->lock_version !== (int) $data['lock_version']) {
                throw ValidationException::withMessages(['lock_version' => 'El expediente cambió. Recárguelo.']);
            }
            if ($row->verified_at === null || $row->location_verified_at === null || $row->latitude === null || $row->critical_site_id !== null) {
                throw ValidationException::withMessages(['criterion' => 'Se requiere una incidencia y ubicación verificadas, sin sitio asociado.']);
            }
            $site = CriticalSite::create(['code' => 'PC-'.Str::upper(Str::random(10)), 'name' => $data['name'], 'address' => $row->address, 'zone_id' => $row->zone_id, 'latitude' => $row->latitude, 'longitude' => $row->longitude,
                'verification_criterion' => $data['criterion'], 'verified_by' => $request->user()->id, 'verified_at' => now(), 'status' => 'activo']);
            $row->critical_site_id = $site->id;
            $row->lock_version++;
            $row->save();
            DB::table('critical_site_history')->insert(['critical_site_id' => $site->id, 'status' => 'activo', 'note' => $data['criterion'], 'user_id' => $request->user()->id, 'created_at' => now()]);
            $workflow->history($row, 'site_linked', $row->status_code, 'Sitio crítico verificado creado y asociado.', $request->user(), ['critical_site_id' => $site->id]);

            return $site;
        });

        return redirect()->route('sites.show', $site)->with('status', 'Sitio crítico creado con criterio explícito y ubicación verificada.');
    }

    public function update(CriticalSiteRequest $request, CriticalSite $site)
    {
        Gate::authorize('manage', Incident::class);
        $data = $request->validated();
        DB::transaction(function () use ($site, $data, $request) {
            $row = CriticalSite::lockForUpdate()->findOrFail($site->id);
            if ($row->lock_version !== (int) $data['lock_version']) {
                throw ValidationException::withMessages(['lock_version' => 'Otro usuario actualizó el sitio. Recargue la página.']);
            }
            if ($data['status'] === 'cerrado' && $row->incidents()->whereNotIn('status_code', ['cerrada', 'rechazada'])->exists()) {
                throw ValidationException::withMessages(['status' => 'Hay incidencias abiertas vinculadas. Deben revisarse antes de cerrar el sitio.']);
            }
            if ($data['status'] !== 'activo' && ! $row->incidents()->whereNotNull('attended_at')->whereNotNull('attention_summary')->exists()) {
                throw ValidationException::withMessages(['status' => 'Se requiere una intervención documentada en alguna incidencia vinculada.']);
            }
            $row->status = $data['status'];
            $row->lock_version++;
            $row->save();
            DB::table('critical_site_history')->insert(['critical_site_id' => $row->id, 'status' => $row->status, 'note' => $data['note'], 'user_id' => $request->user()->id, 'created_at' => now()]);
            Audit::record('critical_site.status_changed', 'site:'.$row->id, ['status' => $row->status]);
        });

        return back()->with('status', 'Estado del sitio actualizado por revisión explícita.');
    }
}
