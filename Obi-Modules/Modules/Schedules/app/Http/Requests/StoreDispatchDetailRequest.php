<?php

namespace Modules\Schedules\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispatch_id'      => 'required|integer|exists:schedules_db.dispatches,id',
            'movement_type_id' => 'required|integer|exists:schedules_db.movement_types,id',
            'cases'            => 'nullable|string',
            'origin'           => 'required|integer',
            'destination'      => 'required|integer',
            'km_traveled'      => 'required|integer|min:0',
            'fare_value'       => 'nullable|integer|min:0',
            'comments'         => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.integer'  => 'El campo :attribute debe ser un número entero.',
            '*.string'   => 'El campo :attribute debe ser texto.',
            '*.min'      => 'El campo :attribute debe ser al menos :min.',
            '*.exists'   => 'El elemento seleccionado en :attribute no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'dispatch_id'      => 'despacho',
            'movement_type_id' => 'tipo de movimiento',
            'cases'            => 'casos',
            'origin'           => 'origen',
            'destination'      => 'destino',
            'km_traveled'      => 'kilómetros recorridos',
            'fare_value'       => 'valor de tarifa',
            'comments'         => 'comentarios',
        ];
    }
}
