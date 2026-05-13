<?php

namespace Modules\Customers\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

            // DNI único, ignorando el registro actual (id auto-detectado)
            'dni'            => [
                'sometimes','nullable','string','max:15',
                Rule::unique('customers_db.customers', 'dni')
                    ->ignore($this->currentCustomerId()),
            ],
            'serial_number'  => ['sometimes','nullable','string','max:15'],
            'email'          => ['sometimes','email'],
            'address'        => ['sometimes','nullable','string','max:255'],
            'phone'          => ['sometimes','string','max:20'],
            'phone2'         => ['sometimes','nullable','string','max:20'],
            'gender'         => ['nullable','in:M,F,O'],
            'marital_status' => ['sometimes','nullable','string','in:Casada,Casado,Conviviente Civil,Divorciada,Divorciado,Separada,Separado,Soltera,Soltero,Unión Civil,Viuda,Viudo'],
            'occupation'     => ['sometimes','nullable','string','max:100'],
            'nationality'    => ['sometimes','nullable','string','in:Chilena,Venezolana,Peruana,Argentina,Colombiana,Brasileña'],
            'commune_id'     => ['sometimes','nullable','exists:geography_db.communes,id'],
            'assigned_agent' => ['sometimes','nullable','exists:traro_db.users,id'],
            'is_enabled'     => ['sometimes','boolean'],
            'comments'       => ['sometimes','nullable','string'],
            'tags'           => ['sometimes','array'],
        ];
    }

    private function currentCustomerId(): ?int
    {
        $v = $this->route('customer')    // puede ser modelo o id
          ?? $this->route('id')
          ?? $this->input('id')
          ?? $this->input('customer_id');

        return is_object($v) ? ($v->id ?? null) : $v;
    }

    public function messages(): array
    {
        return [
            '*.required'      => 'El campo :attribute es obligatorio.',
            '*.string'        => 'El campo :attribute debe ser texto.',
            '*.max'           => 'El campo :attribute no puede superar :max caracteres.',
            '*.email'         => 'El campo :attribute debe ser un correo válido.',
            '*.in'            => 'El campo :attribute contiene un valor no permitido.',
            '*.nullable'      => 'El campo :attribute puede estar vacío.',
            '*.exists'        => 'El :attribute seleccionado no existe.',
            'dni.unique'      => 'El RUT ya está registrado por otro cliente.',
            'comments.string' => 'Los comentarios deben ser texto.',
            'tags.array'      => 'El campo tags debe ser un arreglo JSON.',
        ];
    }
}
