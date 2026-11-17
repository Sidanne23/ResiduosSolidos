<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentCatalogRequest;
use App\Http\Requests\IncidentConfigurationRequest;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\OperationalZone;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;

class IncidentCatalogController extends Controller
{
    public function index()
    {
        return view('incidents.catalogs', ['types' => IncidentType::all(), 'zones' => OperationalZone::all(), 'states' => IncidentStatus::orderBy('position')->get(),
            'transitions' => DB::table('incident_status_transitions')->orderBy('id')->get(), 'duplicateSettings' => json_decode(DB::table('operational_settings')->where('key', 'duplicate_detection')->value('value'), true)]);
    }

    public function store(IncidentCatalogRequest $request)
    {
        $data = $request->validated();
        $class = $data['catalog'] === 'type' ? IncidentType::class : OperationalZone::class;
        $row = $class::create(['name' => $data['name'], 'active' => true]);
        Audit::record('incident.catalog.created', $data['catalog'].':'.$row->id);

        return back()->with('status', 'Elemento del catálogo creado.');
    }

    public function update(IncidentCatalogRequest $request, string $catalog, int $id)
    {
        abort_unless(in_array($catalog, ['type', 'zone'], true), 404);
        $class = $catalog === 'type' ? IncidentType::class : OperationalZone::class;
        $row = $class::findOrFail($id);
        $data = $request->validated();
        $row->update($data);
        Audit::record('incident.catalog.updated', $catalog.':'.$row->id, $data);

        return back()->with('status', 'Catálogo actualizado sin borrar registros asociados.');
    }

    public function settings(IncidentConfigurationRequest $request)
    {
        $data = $request->validated();
        DB::transaction(function () use ($data) {
            foreach ($data['labels'] as $code => $label) {
                IncidentStatus::where('code', $code)->update(['name' => $label]);
            }
            DB::table('incident_status_transitions')->update(['enabled' => false]);
            DB::table('incident_status_transitions')->whereIn('id', $data['transitions'] ?? [])->update(['enabled' => true]);
            DB::table('operational_settings')->where('key', 'duplicate_detection')->update(['value' => json_encode(['distance_m' => $data['distance_m'], 'hours' => $data['hours']])]);
            Audit::record('incident.settings.updated', 'operational_settings');
        });

        return back()->with('status', 'Parámetros técnicos y flujo actualizados.');
    }
}
