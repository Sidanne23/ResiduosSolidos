<?php

namespace App\Http\Requests;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriticalSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Incident::class);
    }

    public function rules(): array
    {
        return ['lock_version' => ['required', 'integer', 'min:1']] + ($this->isMethod('post') ?
            ['name' => ['required', 'string', 'min:5', 'max:180'], 'criterion' => ['required', 'string', 'min:20', 'max:5000']] :
            ['status' => ['required', Rule::in(['activo', 'atendido', 'cerrado'])], 'note' => ['required', 'string', 'min:20', 'max:5000']]);
    }
}
