<?php

use App\Models\ImportBatch;
use App\Models\Source;
use App\Services\EvidenceQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;

// Muestra técnica del reporte sobre la evidencia documental; no inserta registros.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$query = app(EvidenceQuery::class);
$filters = [];
$records = $query->records($filters)->get();
$indicators = $query->indicators($filters);
$issued = now()->timezone('America/Lima')->format('d/m/Y H:i').' (Lima)';
$sources = Source::whereIn('id', $records->pluck('source_id'))->get();
$hashes = ImportBatch::whereIn('id', $records->pluck('import_batch_id'))->pluck('sha256')->all();
$directory = base_path('.tools/report-qa');
File::ensureDirectoryExists($directory);
Pdf::loadView('reports.documentary', compact('filters', 'records', 'indicators', 'issued', 'sources', 'hashes'))
    ->setPaper('a4', 'landscape')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false])->save($directory.'/reporte-final.pdf');
echo "Reporte de verificación generado en .tools/report-qa/reporte-final.pdf\n";
