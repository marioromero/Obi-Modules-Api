<?php

namespace Modules\Schedules\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispatchDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispatch_id'      => 'sometimes|integer|exists:schedules_db.dispatches,id',
            'movement_type_id' => 'sometimes|integer|exists:schedules_db.movement_types,id',
            'cases'            => 'sometimes|nullable|string',
            'origin'           => 'sometimes|integer',
            'destination'      => 'sometimes|integer',
            'km_traveled'      => 'sometimes|integer|min:0',
            'fare_value'       => 'sometimes|nullable|integer|min:0',
            'comments'         => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            '*.integer' => 'El campo :attribute debe ser un número entero.',
            '*.string'  => 'El campo :attribute debe ser texto.',
            '*.min'     => 'El campo :attribute debe ser al menos :min.',
            '*.exists'  => 'El elemento seleccionado en :attribute no existe.',
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
