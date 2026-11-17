<?php

namespace App\Http\Controllers;

use App\Http\Requests\EvidenceFilterRequest;
use App\Models\DocumentaryRecord;
use App\Models\DocumentaryRoute;
use App\Models\EvidenceFile;
use App\Models\QualityIssue;
use App\Models\Source;
use App\Services\EvidenceQuery;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function dashboard(EvidenceFilterRequest $request, EvidenceQuery $query)
    {
        $filters = $request->validated();
        $view = $filters['view'] ?? 'ejecutiva';
        $records = $query->records($filters)->get();
        $indicators = $query->indicators($filters);
        $routes = $query->routes($filters);
        $sourceIds = Source::when($filters['source'] ?? null, fn ($q, $s) => $q->where('code', $s))
            ->when($filters['year'] ?? null, fn ($q, $y) => $q->where('year', $y))->pluck('id');
        if (array_filter(array_intersect_key($filters, array_flip(['process', 'waste_type', 'period', 'q'])))) {
            $sourceIds = $sourceIds->intersect($records->pluck('source_id'));
        }
        $issues = QualityIssue::whereIn('source_id', $sourceIds)->orderBy('code')->get();

        return view('dashboard', compact('filters', 'view', 'records', 'indicators', 'routes', 'issues'));
    }

    public function records(EvidenceFilterRequest $request, EvidenceQuery $query)
    {
        $filters = $request->validated();
        $records = $query->records($filters)->paginate(15)->withQueryString();

        return view('records.index', compact('records', 'filters'));
    }

    public function record(DocumentaryRecord $record)
    {
        $record->load('source', 'batch');
        $issues = QualityIssue::get()->filter(fn ($q) => in_array($record->code, $q->record_codes));

        return view('records.show', compact('record', 'issues'));
    }

    public function sources()
    {
        return view('sources', ['sources' => Source::withCount('records')->orderBy('id')->get(), 'files' => EvidenceFile::all()]);
    }

    public function routes()
    {
        return view('routes', ['routes' => DocumentaryRoute::orderBy('number')->get()]);
    }

    public function download(EvidenceFile $file)
    {
        abort_unless(Storage::disk('local')->exists($file->private_path), 404);

        return Storage::disk('local')->download($file->private_path, $file->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
