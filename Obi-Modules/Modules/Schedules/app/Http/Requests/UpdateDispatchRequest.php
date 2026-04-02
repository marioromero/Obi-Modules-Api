<?php

namespace Modules\Schedules\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'         => 'sometimes|date',
            'assistant_id' => 'sometimes|integer',
        ];
    }

    public function messages(): array
    {
        return [
            '*.date'    => 'El campo :attribute debe ser una fecha válida (AAAA-MM-DD).',
            '*.integer' => 'El campo :attribute debe ser un número entero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date'         => 'fecha',
            'assistant_id' => 'asistente',
        ];
    }
}
