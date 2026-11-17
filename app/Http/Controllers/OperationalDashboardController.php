<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentFilterRequest;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\OperationalZone;
use App\Models\User;
use App\Services\Geography;
use App\Services\IncidentQuery;
use App\Services\OperationalIndicators;

class OperationalDashboardController extends Controller
{
    public function __invoke(IncidentFilterRequest $request, IncidentQuery $query)
    {
        $filters = $request->validated();
        $base = $query->build($request->user(), $filters);
        $summary = app(OperationalIndicators::class)->summarize($request->user(), $filters);

        return view('operations.dashboard', compact('filters') + $summary + [
            'latest' => (clone $base)->latest('detected_at')->limit(8)->get(), 'geography' => app(Geography::class)->feature(),
            'types' => IncidentType::all(), 'zones' => OperationalZone::all(), 'states' => IncidentStatus::orderBy('position')->get(),
            'responsibles' => User::where('active', true)->orderBy('name')->get()]);
    }
}
