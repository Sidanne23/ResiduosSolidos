<?php

namespace App\Services;

use App\Models\DocumentaryRecord;
use App\Models\DocumentaryRoute;
use App\Models\ImportBatch;
use App\Models\QualityIssue;
use App\Models\Source;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;
use ZipArchive;

class WorkbookImport
{
    private const HEADERS = [
        'Registros' => ['ID', 'Año', 'Indicador publicado', 'Fuente', 'Valor numérico', 'Unidad', 'Operador', 'Período', 'Observación', 'Localizador'],
        'Rutas2024' => ['Ruta', 'Lugar', 'Participantes registrados', 'Día', 'Horario', 'Tipo de residuo', 'Fuente', 'Corte'],
        'Procedencia' => ['Fuente', 'Documento', 'Año', 'Localizador', 'Período declarado', 'URL institucional', 'Acceso y alcance', 'Fecha de consulta'],
        'Observaciones' => ['Caso', 'Fuente', 'Hallazgo', 'Tratamiento analítico', 'Dato requerido'],
    ];

    public function preview(string $path, string $name): ImportBatch
    {
        $sha = hash_file('sha256', $path);
        if ($known = ImportBatch::where('sha256', $sha)->first()) {
            return $known;
        }
        $this->validateArchive($path);
        $reader = new Xlsx;
        $reader->setReadDataOnly(false);
        $book = $reader->load($path);
        $names = $book->getSheetNames();
        sort($names);
        $expected = ['Indicadores', 'Observaciones', 'Procedencia', 'Registros', 'Rutas2024'];
        $this->require($names === $expected, 'Se requieren exactamente las cinco hojas documentadas.');
        $raw = [];
        foreach ($book->getAllSheets() as $sheet) {
            $this->require($sheet->getHighestDataRow() <= 500 && Coordinate::columnIndexFromString($sheet->getHighestDataColumn()) <= 26, 'Hoja fuera del tamaño autorizado.');
            $raw[$sheet->getTitle()] = $sheet->toArray(null, false, false, false);
        }
        foreach (self::HEADERS as $sheet => $headers) {
            $this->require(array_slice($raw[$sheet][0], 0, count($headers)) === $headers, "Encabezados incorrectos en {$sheet}.");
        }
        $this->require(array_slice($raw['Indicadores'][4], 0, 5) === ['Indicador calculado', 'Resultado', 'Unidad', 'Período', 'Interpretación'], 'Encabezado de Indicadores inválido.');
        $data = [];
        foreach (self::HEADERS as $sheet => $headers) {
            $data[$sheet] = array_values(array_filter(array_slice($raw[$sheet], 1), fn ($r) => $r[0] !== null));
        }
        $ids = array_column($data['Registros'], 0);
        $this->require(count($ids) === 41 && count(array_unique($ids)) === 41 && $ids === array_map(fn ($n) => sprintf('R%02d', $n), range(1, 41)), 'El mapeo inicial exige R01–R41 únicos y en su orden original.');
        $sourceCodes = array_column($data['Procedencia'], 0);
        $this->require($sourceCodes === ['M24', 'M25', 'M26', 'E26', 'DA26', 'C25', 'C26'], 'Procedencia debe conservar las siete fuentes documentadas.');
        foreach ($data['Procedencia'] as $row) {
            $this->require(is_int($row[2]) && is_numeric($row[7]) && filter_var($row[5], FILTER_VALIDATE_URL) !== false, 'Fuente con año, URL o fecha de consulta inválidos.');
        }
        $units = ['%', 'h/día', 'vehículos', 'vehículo', 'rutas', 'S/', 't', 'puntos', 'actividades', 'participantes', 'km/día', 't/día', 't/mes'];
        foreach ($data['Registros'] as $r) {
            $this->require(in_array($r[3], $sourceCodes, true) && in_array($r[5], $units, true), "Fuente o unidad inválida en {$r[0]}.");
            $this->require(is_int($r[1]) && $r[1] >= 2024 && $r[1] <= 2026 && (is_int($r[4]) || is_float($r[4])) && $r[4] >= 0, "Tipo numérico incorrecto en {$r[0]}.");
            $this->require(in_array($r[6], ['Exacto', 'Mayor que'], true), "Operador inválido en {$r[0]}.");
            $this->require(! empty($r[2]) && ! empty($r[7]) && ! empty($r[9]), "Falta contexto documental en {$r[0]}.");
            if (in_array($r[0], ['R35', 'R36'])) {
                $this->require($r[6] === 'Mayor que', 'R35 y R36 deben conservar la desigualdad estricta.');
            }
        }
        $this->require(array_column($data['Rutas2024'], 0) === range(1, 11), 'Se requieren las once rutas documentadas.');
        foreach ($data['Rutas2024'] as $r) {
            $this->require(is_int($r[2]) && $r[2] >= 0 && $r[6] === 'M24' && $r[5] === 'Inorgánico' && ! empty($r[1]) && ! empty($r[7]), 'Ruta con tipo, fuente, cantidad o contexto inválido.');
        }
        $this->require(array_column($data['Observaciones'], 0) === array_map(fn ($n) => sprintf('Q%02d', $n), range(1, 9)), 'Se requieren las nueve observaciones Q01–Q09.');
        foreach ($data['Observaciones'] as $r) {
            $this->require(in_array($r[1], $sourceCodes, true) && ! empty($r[2]) && ! empty($r[3]) && ! empty($r[4]), 'Observación sin fuente o contexto.');
        }
        $this->require(hash_equals('b88fc16e4984d3e1f3c0bf98bf7acf2766ec80ebf9165bc6c13e298f2faacee8', $sha), 'Este mapeo inicial admite el Excel adjunto verificado. Otros archivos requieren una versión de mapeo y revisión documental.');
        $book->disconnectWorksheets();
        $target = "imports/{$sha}.xlsx";
        Storage::disk('local')->put($target, file_get_contents($path));

        return ImportBatch::create([
            'sha256' => $sha, 'original_name' => basename($name), 'private_path' => $target,
            'created_by' => Auth::id(), 'status' => 'preview',
            'summary' => ['registros' => 41, 'rutas' => 11, 'fuentes' => 7, 'observaciones' => 9],
            'payload' => ['mapping_version' => 'amarilis-1', 'data' => $data, 'raw' => $raw],
        ]);
    }

    public function confirm(ImportBatch $batch): ImportBatch
    {
        $path = Storage::disk('local')->path($batch->private_path);
        $this->require(is_file($path) && hash_equals($batch->sha256, hash_file('sha256', $path)), 'La huella del original cambió; vuelva a validar.');
        try {
            return DB::transaction(function () use ($batch) {
                $batch = ImportBatch::lockForUpdate()->findOrFail($batch->id);
                if ($batch->status === 'completed') {
                    return $batch;
                }
                $this->require(! DocumentaryRecord::exists(), 'Ya existe un corte documental cargado. La gestión de nuevas versiones se implementará sin sobrescribir la evidencia.');
                $data = $batch->payload['data'];
                $sources = [];
                foreach ($data['Procedencia'] as $r) {
                    $sources[$r[0]] = Source::create([
                        'code' => $r[0], 'title' => $r[1], 'year' => $r[2], 'locator' => $r[3],
                        'period' => $r[4], 'url' => $r[5], 'access_note' => $r[6],
                        'consulted_on' => Date::excelToDateTimeObject($r[7])->format('Y-m-d'), 'import_batch_id' => $batch->id,
                    ])->id;
                }
                foreach ($data['Registros'] as $i => $r) {
                    DocumentaryRecord::create(array_merge(RecordSemantics::forCode($r[0]), [
                        'code' => $r[0], 'source_id' => $sources[$r[3]], 'year' => $r[1], 'label' => $r[2],
                        'value' => number_format($r[4], 10, '.', ''), 'unit' => $r[5], 'operator' => $r[6] === 'Mayor que' ? '>' : '=',
                        'period' => $r[7], 'note' => $r[8], 'locator' => $r[9], 'source_row' => $i + 2,
                        'import_batch_id' => $batch->id,
                    ]));
                }
                foreach ($data['Rutas2024'] as $i => $r) {
                    DocumentaryRoute::create([
                        'source_id' => $sources[$r[6]], 'import_batch_id' => $batch->id, 'source_row' => $i + 2,
                        'number' => $r[0], 'place' => $r[1], 'participants' => $r[2], 'day' => $r[3],
                        'schedule' => $r[4], 'waste_type' => $r[5], 'period' => $r[7],
                    ]);
                }
                foreach ($data['Observaciones'] as $r) {
                    QualityIssue::create(['code' => $r[0], 'source_id' => $sources[$r[1]], 'finding' => $r[2],
                        'treatment' => $r[3], 'required_evidence' => $r[4], 'record_codes' => RecordSemantics::ISSUE_RECORDS[$r[0]]]);
                }
                $batch->update(['status' => 'completed', 'confirmed_at' => now(), 'failure' => null]);
                Audit::record('import.completed', "batch:{$batch->id}", $batch->summary);

                return $batch;
            });
        } catch (Throwable $e) {
            $batch->refresh();
            if ($batch->status !== 'completed') {
                $batch->update(['status' => 'failed', 'failure' => 'La carga se revirtió. Revise las validaciones y el registro técnico.']);
            }
            report($e);
            throw $e;
        }
    }

    private function validateArchive(string $path): void
    {
        $this->require(is_file($path) && filesize($path) <= 2 * 1024 * 1024, 'El XLSX no existe o supera 2 MB.');
        $zip = new ZipArchive;
        $this->require($zip->open($path) === true, 'El archivo no es un XLSX válido.');
        $total = 0;
        $this->require($zip->numFiles <= 1000, 'El archivo contiene demasiadas entradas.');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);
            $total += $entry['size'];
            $this->require(! str_contains(strtolower($entry['name']), 'vbaproject') && ! str_contains(strtolower($entry['name']), 'externallinks/'), 'No se admiten macros ni vínculos externos.');
            $this->require($total <= 20 * 1024 * 1024, 'El contenido expandido supera el límite.');
        }
        $zip->close();
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['workbook' => $message]);
        }
    }
}
