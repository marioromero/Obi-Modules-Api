<?php

namespace Modules\Customers\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\app\Rules\ValidatedRut;
use Modules\Geography\Models\Commune;
use Modules\Users\Models\TraroUser;
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /* ──────── REGLAS ──────── */
    public function rules(): array
    {
        return [
            // Datos personales
            'name'           => 'required|string|max:254|regex:/^[\pL\s\-’]+$/u',
            'lastname'       => 'required|string|max:254|regex:/^[\pL\s\-’]+$/u',
            'dni'            => [
                'required',
                'string',
                'regex:/^\d{7,8}-[0-9kK]{1}$/',
                new ValidatedRut,
                Rule::unique('customers_db.customers', 'dni'),  // ← único
            ],
            'username'       => 'nullable|string|max:50',
            'password'       => 'nullable|string|max:255',
            'serial_number'  => 'nullable','string','max:15',
            'email'          => 'required|email|max:255',
            'address'        => 'required|string|max:255',
            'phone'          => 'required|string|regex:/^\d{9,11}$/',
            'phone2'         => 'nullable|string|regex:/^\d{9,11}$/',
            'gender'         => 'nullable|in:M,F',
            'marital_status' => 'required|in:Casada,Casado,Conviviente Civil,Divorciada,Divorciado,Separada,Separado,Soltera,Soltero,Unión Civil,Viuda,Viudo',
            'occupation'     => 'required|string|max:255',
            'nationality'    => 'nullable|in:Chilena,Venezolana,Peruana,Argentina,Colombiana,Brasileña',
            'comments'       => 'nullable', 'string',

            // Relaciones
            'commune_id'     => ['nullable', 'integer', Rule::exists(Commune::class, 'id')],
            'assigned_agent' => ['nullable','integer', Rule::exists(TraroUser::class, 'id')],
        ];
    }

    /* ─────── MENSAJES ─────── */
    public function messages(): array
    {
        return [
            // Requeridos
            'name.required'           => 'El nombre es obligatorio.',
            'lastname.required'       => 'El apellido es obligatorio.',
            'dni.required'            => 'El RUT es obligatorio.',
            'email.required'          => 'El correo es obligatorio.',
            'address.required'        => 'La dirección es obligatoria.',
            'phone.required'          => 'El teléfono es obligatorio.',
            'marital_status.required' => 'El estado civil es obligatorio.',
            'occupation.required'     => 'La ocupación es obligatoria.',

            // Formato y longitud
            'name.regex'              => 'El nombre solo puede contener letras y espacios.',
            'lastname.regex'          => 'El apellido solo puede contener letras y espacios.',
            'dni.regex'               => 'El formato del RUT es inválido (ej. 12345678-9).',
            'phone.regex'             => 'El teléfono debe tener de 9 a 11 dígitos.',
            'phone2.regex'            => 'El teléfono 2 debe tener de 9 a 11 dígitos.',
            'email.email'             => 'El correo no tiene un formato válido.',

            // Reglas personalizadas
            'dni.unique'              => 'El RUT ya está registrado.',
            'dni.validated_rut'       => 'El RUT no es válido.',

            // In list / Exists
            'gender.in'               => 'El género seleccionado no es válido.',
            'marital_status.in'       => 'El estado civil seleccionado no es válido.',
            'nationality.in'          => 'La nacionalidad seleccionada no es válida.',
            'commune_id.exists'       => 'La comuna seleccionada no existe.',
            'assigned_agent.exists'   => 'El usuario asignado no existe.',
            'comments.string'         => 'Los comentarios deben ser texto.',
        ];
    }
}
