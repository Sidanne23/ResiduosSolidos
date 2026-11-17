<?php

namespace App\Http\Requests;

use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\OperationalZone;
use App\Services\Geography;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('incident') ? $this->user()->can('update', $this->route('incident')) : $this->user()->can('create', Incident::class);
    }

    public function rules(): array
    {
        return [
            'incident_type_id' => ['required', 'integer', 'exists:incident_types,id'], 'title' => ['required', 'string', 'min:5', 'max:180'],
            'description' => ['required', 'string', 'min:15', 'max:10000'], 'detected_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'address' => ['required', 'string', 'min:5', 'max:2000'], 'zone_id' => ['nullable', 'integer', 'exists:operational_zones,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'location_source' => ['nullable', 'required_with:latitude', Rule::in(['mapa', 'gps_documentado', 'documento', 'coordenadas'])],
            'reported_by_label' => ['required', 'string', 'max:180'], 'observations' => ['nullable', 'string', 'max:5000'],
            'priority' => [$this->user()->can('manage', Incident::class) ? 'nullable' : 'prohibited', Rule::in(['baja', 'media', 'alta', 'urgente'])],
            'priority_reason' => [$this->user()->can('manage', Incident::class) ? 'nullable' : 'prohibited', 'required_with:priority', 'string', 'min:10', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:3'], 'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'lock_version' => [$this->route('incident') ? 'required' : 'nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->filled('latitude') && $this->filled('longitude') && ! $validator->errors()->has('latitude') && ! $validator->errors()->has('longitude')
                && ! app(Geography::class)->contains((float) $this->input('latitude'), (float) $this->input('longitude'))) {
                $validator->errors()->add('latitude', 'La ubicación está fuera del ámbito cartográfico de Amarilis disponible. Revise el punto.');
            }
            $type = IncidentType::find($this->input('incident_type_id'));
            if ($type && ! $type->active && $this->route('incident')?->incident_type_id !== $type->id) {
                $validator->errors()->add('incident_type_id', 'Seleccione un tipo activo.');
            }
            $zone = OperationalZone::find($this->input('zone_id'));
            if ($zone && ! $zone->active && $this->route('incident')?->zone_id !== $zone->id) {
                $validator->errors()->add('zone_id', 'Seleccione una zona activa.');
            }
            if (! $validator->errors()->has('detected_at')) {
                $date = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->input('detected_at'), 'America/Lima');
                if ($date->isFuture()) {
                    $validator->errors()->add('detected_at', 'La detección no puede estar en el futuro. La hora se interpreta en Lima.');
                }
            }
        }];
    }
}
