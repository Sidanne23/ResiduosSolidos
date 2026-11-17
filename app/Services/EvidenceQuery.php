<?php

namespace App\Services;

use App\Models\DocumentaryRecord;
use App\Models\DocumentaryRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EvidenceQuery
{
    public function records(array $filters): Builder
    {
        return DocumentaryRecord::with('source')
            ->when($filters['source'] ?? null, fn ($q, $s) => $q->whereHas('source', fn ($q) => $q->where('code', $s)))
            ->when($filters['year'] ?? null, fn ($q, $y) => $q->where('year', $y))
            ->when($filters['process'] ?? null, fn ($q, $v) => $q->where('process', $v))
            ->when($filters['waste_type'] ?? null, fn ($q, $v) => $q->where('waste_type', $v))
            ->when($filters['period'] ?? null, fn ($q, $v) => $q->where('period', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('label', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%")))
            ->orderBy('code');
    }

    public function routes(array $filters): Collection
    {
        // Los filtros de registros publicados no redefinen el universo del cuadro de rutas.
        if (($filters['source'] ?? 'M24') !== 'M24' || (string) ($filters['year'] ?? 2024) !== '2024'
            || ! empty($filters['period']) || ! empty($filters['q'])
            || (isset($filters['process']) && $filters['process'] !== 'Segregación')
            || (isset($filters['waste_type']) && $filters['waste_type'] !== 'Inorgánico')) {
            return collect();
        }

        return DocumentaryRoute::orderBy('number')->get();
    }

    public function indicators(array $filters): array
    {
        return (new IndicatorEngine)->calculate($this->records($filters)->get(), $this->routes($filters));
    }
}
