<?php

namespace Modules\Schedules\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'case_id'          => 'sometimes|integer|exists:cases_db.cases,id',
            'insurer_id'       => 'sometimes|nullable|integer|exists:banks_db.insurers,id',
            'loss_adjuster_id' => 'sometimes|nullable|integer|exists:banks_db.loss_adjusters,id',
            'consultant_id'    => 'sometimes|nullable|integer|exists:traro_db.users,id',

            //Datos de agenda
            'inspection_date'  => 'sometimes|nullable|date',
            'inspection_time'  => ['sometimes','nullable','string','size:5','regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'accident_number'  => 'sometimes|nullable|integer|min:0',
            'comments'         => 'sometimes|nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.integer'  => 'El campo :attribute debe ser un número entero.',
            '*.numeric'  => 'El campo :attribute debe ser numérico.',
            '*.string'   => 'El campo :attribute debe ser texto.',
            '*.boolean'  => 'El campo :attribute debe ser verdadero o falso.',
            '*.date'     => 'El campo :attribute debe ser una fecha válida (AAAA-MM-DD).',
            'inspection_time.size'  => 'La :attribute debe tener el formato HH:MM (5 caracteres).',
            'inspection_time.regex' => 'La :attribute debe estar en formato 24h válido (00:00 a 23:59).',
            '*.max'      => 'El campo :attribute no puede superar :max caracteres.',
            '*.min'      => 'El campo :attribute debe ser al menos :min.',
            '*.exists'   => 'El elemento seleccionado en :attribute no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            // Relaciones
            'case_id'          => 'caso',
            'insurer_id'       => 'aseguradora',
            'loss_adjuster_id' => 'liquidadora',
            'consultant_id'    => 'asesor',

            // Datos de agenda
            'inspection_date'  => 'fecha de visita',
            'inspection_time'  => 'hora de visita',
            'accident_number'  => 'número de siniestro',
            'comments'         => 'comentarios',
        ];
    }
}
