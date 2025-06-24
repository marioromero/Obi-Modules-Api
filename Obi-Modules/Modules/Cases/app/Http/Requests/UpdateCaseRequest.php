<?php

namespace Modules\Cases\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseRequest extends FormRequest
{
    /**
     * Autoriza la petición (ajusta según policies/gates si lo necesitas).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para actualización parcial (PATCH).
     * Cada campo está precedido por «sometimes» para que solo se
     * valide cuando venga presente en el payload.
     */
    public function rules(): array
    {
        return [
            /* ─────── Relaciones ─────── */
            'customer_id'          => 'sometimes|nullable|integer',
            'agreement_id'         => 'sometimes|nullable|integer',
            'commune_id'           => 'sometimes|nullable|integer',
            'accident_type_id'     => 'sometimes|nullable|integer',
            'agent_id'             => 'sometimes|nullable|integer',
            'loss_adjuster_id'     => 'sometimes|nullable|integer',
            'insurer_id'           => 'sometimes|nullable|integer',
            'consultant_id'        => 'sometimes|nullable|integer',

            /* ─────── Datos generales ─────── */
            'property_address'     => 'sometimes|required|string|max:255',
            'property_type'        => 'sometimes|required|string|in:Casa,Departamento,Otro',
            'is_duplicated'        => 'sometimes|required|boolean',

            /* ─────── Fechas ─────── */
            'complaint_date'         => 'sometimes|nullable|date',
            'date_of_loss'           => 'sometimes|nullable|date',
            'inspection_date'        => 'sometimes|nullable|date',
            'budget_sending_date'    => 'sometimes|nullable|date',
            'contestation_date'      => 'sometimes|nullable|date',
            'settlement_report_date' => 'sometimes|nullable|date',
            'probable_payment_date'  => 'sometimes|nullable|date',
            'collection_date'        => 'sometimes|nullable|date',
            'online_collection_date' => 'sometimes|nullable|date',

            /* ─────── Identificadores externos ─────── */
            'bank_service_number'  => 'sometimes|nullable|string|max:50',
            'accident_number'      => 'sometimes|nullable|string|max:50',

            /* ─────── Montos ─────── */
            'approved_amount'      => 'sometimes|nullable|numeric|min:0',
            'uf_approved'          => 'sometimes|nullable|numeric|min:0',
            'advisory_amount'      => 'sometimes|nullable|numeric|min:0',
            'amount_paid'          => 'sometimes|nullable|numeric|min:0',
            'amount_owed'          => 'sometimes|nullable|numeric|min:0',

            /* ─────── Estado de pago ─────── */
            'payment_status'       => 'sometimes|nullable|string|max:50',
        ];
    }

    /**
     * Mensajes de error básicos en español.
     */
    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.integer'  => 'El campo :attribute debe ser numérico.',
            '*.numeric'  => 'El campo :attribute debe ser numérico.',
            '*.date'     => 'El campo :attribute debe ser una fecha válida (YYYY-MM-DD).',
            '*.boolean'  => 'El campo :attribute debe ser verdadero o falso.',
            '*.in'       => 'El campo :attribute contiene un valor no permitido.',
            '*.max'      => 'El campo :attribute no puede superar :max caracteres.',
            '*.min'      => 'El campo :attribute debe ser al menos :min.',
        ];
    }
}
