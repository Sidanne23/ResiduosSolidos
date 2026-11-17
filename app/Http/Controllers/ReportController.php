<?php

namespace App\Http\Controllers;

use App\Http\Requests\EvidenceFilterRequest;
use App\Models\ImportBatch;
use App\Models\Source;
use App\Services\Audit;
use App\Services\EvidenceQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function export(EvidenceFilterRequest $request, string $format, EvidenceQuery $query)
    {
        $filters = $request->validated();
        $records = $query->records($filters)->get();
        $indicators = $query->indicators($filters);
        $issued = now()->timezone('America/Lima')->format('d/m/Y H:i').' (Lima)';
        $sources = Source::whereIn('id', $records->pluck('source_id'))->get();
        $hashes = ImportBatch::whereIn('id', $records->pluck('import_batch_id'))->pluck('sha256')->all();
        Audit::record('report.exported', $format, ['filters' => $filters, 'records' => $records->count()]);
        if ($format === 'pdf') {
            return Pdf::loadView('reports.documentary', compact('filters', 'records', 'indicators', 'issued', 'sources', 'hashes'))
                ->setPaper('a4', 'landscape')->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false])->download('amarilis-documental.pdf');
        }

        return response()->streamDownload(function () use ($records, $indicators, $issued, $filters, $sources, $hashes) {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet()->setTitle('Registros documentales');
            $this->row($sheet, 1, ['ID', 'Indicador publicado', 'Valor', 'Operador', 'Unidad', 'Fuente', 'Período', 'Proceso', 'Localizador', 'Observaciones y restricciones']);
            foreach ($records as $i => $r) {
                $this->row($sheet, $i + 2, [$r->code, $r->label, $r->value === null ? 'Dato no disponible' : (float) $r->value, $r->operator, $r->unit, $r->source->code, $r->period, $r->process, $r->locator, implode(' ', array_filter([$r->note, $r->qualifier, ...$r->restrictions]))]);
            }
            $sheet->setAutoFilter('A1:J'.max(1, $records->count() + 1));
            $sheet = $book->createSheet()->setTitle('Indicadores calculados');
            $this->row($sheet, 1, ['ID', 'Indicador', 'Resultado', 'Operador', 'Unidad', 'Fuente', 'Corte', 'Fórmula', 'Restricción']);
            foreach (array_values($indicators) as $i => $r) {
                $this->row($sheet, $i + 2, [$r['code'], $r['label'], $r['value'] === null ? 'No calculable con los filtros actuales' : (float) $r['value'], $r['operator'], $r['unit'], $r['source'], $r['period'], $r['formula'], $r['warning']]);
                $sheet->getStyle('C'.($i + 2))->getNumberFormat()->setFormatCode($r['unit'] === '%' ? '0.00%' : '#,##0.0000');
            }
            $sheet = $book->createSheet()->setTitle('Procedencia y alcance');
            $this->row($sheet, 1, ['Concepto', 'Detalle']);
            $this->row($sheet, 2, ['Emisión', $issued]);
            $this->row($sheet, 3, ['Filtros', json_encode($filters, JSON_UNESCAPED_UNICODE)]);
            $this->row($sheet, 4, ['Naturaleza', 'Evidencia documental y cálculos. No acredita implementación municipal ni mejora ambiental.']);
            $this->row($sheet, 5, ['Precisión', 'Excel conserva hasta 15 dígitos significativos; fórmulas semánticas y operandos permiten reproducir el cálculo BCMath.']);
            $this->row($sheet, 6, ['SHA-256 de importación', implode(', ', $hashes)]);
            foreach ($sources as $i => $s) {
                $this->row($sheet, $i + 8, [$s->code, $s->title.' | '.$s->locator.' | '.$s->period.' | '.$s->url]);
            }
            foreach ($book->getAllSheets() as $sheet) {
                $sheet->freezePane('A2');
                $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF164E46');
                $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true)->setVertical('top');
                foreach ($sheet->getColumnIterator() as $column) {
                    $sheet->getColumnDimension($column->getColumnIndex())->setWidth($column->getColumnIndex() === 'A' ? 15 : 35);
                }
            }
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'amarilis-documental.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function row($sheet, int $row, array $values): void
    {
        foreach ($values as $column => $value) {
            // Texto explícito: evita inyección de fórmulas desde etiquetas o fuentes.
            $sheet->setCellValueExplicit([$column + 1, $row], $value, is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
        }
    }
}
