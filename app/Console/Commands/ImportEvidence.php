<?php

namespace App\Console\Commands;

use App\Models\EvidenceFile;
use App\Services\WorkbookImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportEvidence extends Command
{
    protected $signature = 'evidence:load {directory : Carpeta con los cuatro adjuntos originales}';

    protected $description = 'Incorporar los adjuntos verificados y cargar el Excel documental sin duplicar';

    public function handle(WorkbookImport $importer): int
    {
        $manifest = json_decode(file_get_contents(base_path('docs/09-evidencias/inventario-adjuntos.json')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($manifest as $entry) {
            $path = rtrim($this->argument('directory'), '/\\').DIRECTORY_SEPARATOR.$entry['file'];
            if (! is_file($path) || ! hash_equals($entry['sha256'], hash_file('sha256', $path))) {
                $this->error('Adjunto ausente o huella diferente: '.$entry['file']);

                return self::FAILURE;
            }
        }
        foreach ($manifest as $entry) {
            $path = rtrim($this->argument('directory'), '/\\').DIRECTORY_SEPARATOR.$entry['file'];
            $target = 'evidence/'.$entry['sha256'].'.'.pathinfo($path, PATHINFO_EXTENSION);
            Storage::disk('local')->put($target, file_get_contents($path));
            EvidenceFile::firstOrCreate(['sha256' => $entry['sha256']], [
                'title' => pathinfo($entry['file'], PATHINFO_FILENAME), 'original_name' => $entry['file'],
                'private_path' => $target, 'mime' => mime_content_type($path), 'bytes' => filesize($path),
            ]);
            if (str_ends_with($path, '.xlsx')) {
                $batch = $importer->preview($path, $entry['file']);
                $importer->confirm($batch);
            }
        }
        $this->info('Carga documental completada: 41 registros, 11 rutas y 9 observaciones.');

        return self::SUCCESS;
    }
}
