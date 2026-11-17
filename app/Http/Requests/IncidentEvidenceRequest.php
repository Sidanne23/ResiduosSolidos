<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('attach', $this->route('incident'));
    }

    public function rules(): array
    {
        return ['photos' => ['required', 'array', 'max:3'], 'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'phase' => ['required', Rule::in(['antes', 'despues'])], 'caption' => ['required', 'string', 'min:5', 'max:1000'], 'lock_version' => ['required', 'integer', 'min:1']];
    }
}
