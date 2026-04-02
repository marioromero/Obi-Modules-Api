<?php

namespace Modules\Schedules\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.string'   => 'El campo :attribute debe ser texto.',
            '*.max'      => 'El campo :attribute no puede superar :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
        ];
    }
}
