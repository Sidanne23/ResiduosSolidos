<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->active;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:150'], 'from' => ['nullable', 'date_format:Y-m-d'], 'to' => array_filter(['nullable', 'date_format:Y-m-d', $this->filled('from') ? 'after_or_equal:from' : null]),
            'incident_type_id' => ['nullable', 'integer', 'exists:incident_types,id'], 'status_code' => ['nullable', 'exists:incident_statuses,code'],
            'zone_id' => ['nullable', 'integer', 'exists:operational_zones,id'], 'priority' => ['nullable', Rule::in(['baja', 'media', 'alta', 'urgente', 'sin_asignar'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'], 'verified_only' => ['nullable', 'boolean'],
            'location' => ['nullable', Rule::in(['verificada', 'pendiente', 'sin_coordenadas'])]];
    }
}
