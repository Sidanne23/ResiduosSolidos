<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transition', $this->route('incident'));
    }

    public function rules(): array
    {
        return [
            'status_code' => ['required', 'exists:incident_statuses,code'], 'lock_version' => ['required', 'integer', 'min:1'],
            'note' => ['required', 'string', 'min:10', 'max:5000'], 'verify_location' => ['nullable', 'boolean'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('active', true)->whereIn('role', ['administrador', 'analista', 'registrador', 'responsable_limpieza'])],
            'priority' => ['nullable', Rule::in(['baja', 'media', 'alta', 'urgente'])],
            'priority_reason' => ['nullable', 'required_with:priority', 'string', 'min:10', 'max:2000'],
            'attention_summary' => ['nullable', 'string', 'min:20', 'max:5000'],
            'critical_site_id' => ['nullable', 'integer', 'exists:critical_sites,id'],
            'duplicate_of_id' => ['nullable', 'integer', 'exists:incidents,id'],
        ];
    }
}
