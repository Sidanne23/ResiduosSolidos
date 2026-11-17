<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncidentConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('administer');
    }

    public function rules(): array
    {
        return ['distance_m' => ['required', 'integer', 'between:5,5000'], 'hours' => ['required', 'integer', 'between:1,720'],
            'labels' => ['required', 'array:reportada,pendiente_verificacion,verificada,asignada,en_atencion,atendida,cerrada,rechazada', 'size:8'],
            'labels.*' => ['required', 'string', 'max:80'], 'transitions' => ['nullable', 'array'], 'transitions.*' => ['integer', 'exists:incident_status_transitions,id']];
    }
}
