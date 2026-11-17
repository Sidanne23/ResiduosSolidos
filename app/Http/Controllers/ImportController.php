<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportWorkbookRequest;
use App\Models\ImportBatch;
use App\Services\WorkbookImport;

class ImportController extends Controller
{
    public function index()
    {
        return view('imports.index', ['batches' => ImportBatch::latest()->get()]);
    }

    public function store(ImportWorkbookRequest $request, WorkbookImport $importer)
    {
        $file = $request->file('workbook');
        $batch = $importer->preview($file->getRealPath(), $file->getClientOriginalName());

        return redirect()->route('imports.show', $batch);
    }

    public function show(ImportBatch $batch)
    {
        return view('imports.show', compact('batch'));
    }

    public function confirm(ImportBatch $batch, WorkbookImport $importer)
    {
        $importer->confirm($batch);

        return redirect()->route('imports.show', $batch)->with('status', 'Importación confirmada. La evidencia original se conserva.');
    }
}
