<?php

namespace Modules\Banks\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
        ];
    }

    /** Mensajes personalizados */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del banco es obligatorio.',
            'name.string'   => 'El nombre del banco debe ser una cadena de texto válida.',
            'name.max'      => 'El nombre del banco no puede superar los 100 caracteres.',
        ];
    }
}
