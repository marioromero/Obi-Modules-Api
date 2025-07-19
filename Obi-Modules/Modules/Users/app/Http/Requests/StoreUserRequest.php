<?php

namespace Modules\Users\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\app\Rules\ValidatedRut;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules (en el orden solicitado).
     */
    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:254|regex:/^[\pL\s\-’]+$/u',
            'lastname' => 'required|string|max:254|regex:/^[\pL\s\-’]+$/u',
            'dni'      => ['required', 'string', 'regex:/^\d{7,8}-[0-9kK]{1}$/', new ValidatedRut],
            'email'    => 'required|email|max:255|unique:users,email',
            'phone'    => 'required|string|regex:/^\d{9,11}$/',
            'gender'   => 'required|in:M,F',
            'role_id'  => 'required|integer|exists:users_db.roles,id',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required'        => 'El campo nombre es obligatorio.',
            'name.regex'           => 'El nombre solo puede contener letras, espacios o guiones.',
            'lastname.required'    => 'El campo apellido es obligatorio.',
            'lastname.regex'       => 'El apellido solo puede contener letras, espacios o guiones.',
            'dni.required'         => 'El RUT es obligatorio.',
            'dni.regex'            => 'El formato del RUT no es válido.',
            'email.required'       => 'El correo electrónico es obligatorio.',
            'email.email'          => 'Debe ser un correo electrónico válido.',
            'email.unique'         => 'Este correo electrónico ya está registrado.',
            'phone.required'       => 'El teléfono es obligatorio.',
            'phone.regex'          => 'El teléfono debe contener entre 9 y 11 dígitos.',
            'gender.required'      => 'El género es obligatorio.',
            'gender.in'            => 'El género debe ser M o F.',
            'role_id.required'     => 'El rol es obligatorio.',
            'role_id.exists'       => 'El rol seleccionado no existe.',
        ];
    }
}
