<?php

namespace Modules\Users\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\app\Rules\ValidatedRut;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:254',
                'regex:/^[\pL\s\-’]+$/u',
            ],
            'lastname' => [
                'sometimes',
                'string',
                'max:254',
                'regex:/^[\pL\s\-’]+$/u',
            ],
            'dni' => [
                'sometimes',
                'string',
                'regex:/^\d{7,8}-[0-9kK]{1}$/',
                new ValidatedRut,
                Rule::unique('users', 'dni')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'sometimes',
                'string',
                'regex:/^\d{9,11}$/',
            ],
            'gender' => [
                'sometimes',
                Rule::in(['M', 'F']),
            ],
            'role_id' => [
                'sometimes',
                'integer',
                'exists:users_db.roles,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'          => 'El nombre debe ser texto.',
            'name.max'             => 'El nombre no puede superar 254 caracteres.',
            'name.regex'           => 'El nombre solo puede contener letras, espacios o guiones.',

            'lastname.string'      => 'El apellido debe ser texto.',
            'lastname.max'         => 'El apellido no puede superar 254 caracteres.',
            'lastname.regex'       => 'El apellido solo puede contener letras, espacios o guiones.',

            'dni.string'           => 'El RUT debe ser texto.',
            'dni.regex'            => 'El formato del RUT no es válido.',
            'dni.unique'           => 'Este RUT ya está registrado.',

            'email.email'          => 'Debe ser un correo electrónico válido.',
            'email.max'            => 'El correo no puede superar 255 caracteres.',
            'email.unique'         => 'Este correo electrónico ya está registrado.',

            'phone.regex'          => 'El teléfono debe contener entre 9 y 11 dígitos.',

            'gender.in'            => 'El género debe ser M o F.',

            'role_id.integer'      => 'El rol debe ser un número entero.',
            'role_id.exists'       => 'El rol seleccionado no existe.',
        ];
    }
}
