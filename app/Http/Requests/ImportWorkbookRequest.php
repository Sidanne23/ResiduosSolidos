<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportWorkbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import') ?? false;
    }

    public function rules(): array
    {
        return ['workbook' => ['required', 'file', 'mimes:xlsx', 'max:2048']];
    }
}
