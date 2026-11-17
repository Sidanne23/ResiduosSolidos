<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('administer');
    }

    public function rules(): array
    {
        $catalog = $this->route('catalog') ?? $this->input('catalog');
        $table = $catalog === 'zone' ? 'operational_zones' : 'incident_types';
        $rules = ['name' => ['required', 'string', 'max:150', Rule::unique($table, 'name')->ignore($this->route('id'))]];
        if ($this->isMethod('post')) {
            $rules['catalog'] = ['required', Rule::in(['type', 'zone'])];
        } else {
            $rules['active'] = ['required', 'boolean'];
        }

        return $rules;
    }
}
