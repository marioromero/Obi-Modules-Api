<?php

namespace Modules\Customers\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;               // ajusta si necesitas Gate/Policy
    }

    public function rules(): array
    {
        return [
            /* ───── Campos principales ───── */
            'name'           => 'required|string|max:100',
            'lastname'       => 'nullable|string|max:100',
            'dni'            => 'nullable|string|max:20',
            'username'       => 'nullable|string|max:50',
            'password'       => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:15',
            'phone2'         => 'nullable|string|max:15',
            'gender'         => 'nullable|in:M,F,O',        // O = otro (ejemplo)
            'marital_status' => 'nullable|string|max:15',
            'occupation'     => 'nullable|string|max:100',

            /* ───── Relaciones ───── */
            'case_status_id' => 'nullable|integer',
            'commune_id'     => 'nullable|integer',
            'user_id'        => 'nullable|integer',
        ];
    }
}
