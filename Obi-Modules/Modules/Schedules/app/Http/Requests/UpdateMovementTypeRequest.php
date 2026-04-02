<?php

namespace Modules\Schedules\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            '*.string' => 'El campo :attribute debe ser texto.',
            '*.max'    => 'El campo :attribute no puede superar :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
        ];
    }
}
