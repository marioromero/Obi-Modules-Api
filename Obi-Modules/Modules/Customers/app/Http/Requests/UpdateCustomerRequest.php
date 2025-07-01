<?php

namespace Modules\Customers\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['sometimes','string','max:100'],
            'lastname'       => ['sometimes','string','max:100'],
            'dni'            => ['sometimes','string','max:15'],
            'email'          => ['sometimes','email'],
            'address'        => ['sometimes','string','max:255'],
            'phone'          => ['sometimes','string','max:20'],
            'phone2'         => ['sometimes','nullable','string','max:20'],
            'gender'         => ['sometimes','in:M,F,O'],
            'marital_status' => ['sometimes','in:Casada,Casado,Conviviente Civil,Divorciada,Divorciado,Separada,Separado,Soltera,Soltero,Unión Civil,Viuda,Viudo'],
            'occupation'     => ['sometimes','string','max:100'],
            'commune_id'     => ['sometimes','nullable','exists:geography_communes,id'],
        ];
    }

    /**
     * Mensajes de error en español para validación.
     */
    public function messages(): array
    {
        return [
            '*.required'       => 'El campo :attribute es obligatorio.',
            '*.string'         => 'El campo :attribute debe ser texto.',
            '*.max'            => 'El campo :attribute no puede superar :max caracteres.',
            '*.email'          => 'El campo :attribute debe ser un correo válido.',
            '*.in'             => 'El campo :attribute contiene un valor no permitido.',
            '*.nullable'       => 'El campo :attribute puede estar vacío.',
            '*.exists'         => 'El :attribute seleccionado no existe.',
        ];
    }
}
