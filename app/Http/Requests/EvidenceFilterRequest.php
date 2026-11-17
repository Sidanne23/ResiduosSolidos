<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvidenceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->active ?? false;
    }

    public function rules(): array
    {
        return [
            'source' => ['nullable', Rule::in(['M24', 'M25', 'M26', 'E26', 'DA26', 'C25', 'C26'])],
            'year' => ['nullable', 'integer', Rule::in([2024, 2025, 2026])],
            'process' => ['nullable', 'string', 'max:64'], 'waste_type' => ['nullable', Rule::in(['Orgánico', 'Inorgánico'])],
            'period' => ['nullable', 'string', 'max:300'], 'q' => ['nullable', 'string', 'max:150'],
            'view' => ['nullable', Rule::in(['ejecutiva', 'operativa', 'calidad'])],
        ];
    }
}
