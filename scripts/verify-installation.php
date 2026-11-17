<?php

use App\Models\DocumentaryRecord;
use App\Models\DocumentaryRoute;
use App\Models\EvidenceFile;
use App\Models\QualityIssue;
use App\Models\Source;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$result = [
    'php' => PHP_VERSION, 'laravel' => app()->version(),
    'database_driver' => DB::connection()->getDriverName(),
    'database_name' => config('database.connections.mysql.database'),
    'mysql_server' => DB::selectOne('SELECT VERSION() AS version')->version,
    'counts' => [
        'records' => DocumentaryRecord::count(), 'routes' => DocumentaryRoute::count(),
        'sources' => Source::count(), 'issues' => QualityIssue::count(),
        'files' => EvidenceFile::count(), 'users' => User::count(),
        'operational_incidents' => \App\Models\Incident::count(), 'verified_critical_sites' => \App\Models\CriticalSite::count(),
    ],
    'original_files_verified' => EvidenceFile::all()->every(fn ($f) => hash_equals($f->sha256, hash_file('sha256', Storage::disk('local')->path($f->private_path)))),
];
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
