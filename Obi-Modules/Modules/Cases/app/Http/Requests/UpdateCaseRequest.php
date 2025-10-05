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
     * Reglas de validación para actualización parcial (PATCH/PUT).
     * «sometimes» hace que el campo sólo se valide si viene presente.
     */
    public function rules(): array
    {
        return [
            /* ─────── Relaciones ─────── */
            'bank_id'              => 'sometimes|nullable|integer|exists:banks_db.banks,id',
            'customer_id'          => 'sometimes|nullable|integer|exists:customers_db.customers,id',
            'agreement_id'         => 'sometimes|nullable|integer|exists:cases_db.agreements,id',
            'commune_id'           => 'sometimes|nullable|integer|exists:geography_db.communes,id',
            'accident_type_id'     => 'sometimes|nullable|integer|exists:cases_db.accident_types,id',
            'agent_id'             => 'sometimes|nullable|integer|exists:traro_db.users,id',
            'consultant_id'        => 'sometimes|nullable|integer|exists:traro_db.users,id',
            'assigned_user'        => 'sometimes|nullable|integer|exists:traro_db.users,id',
            'created_by'           => 'sometimes|nullable|integer|exists:traro_db.users,id',
            'loss_adjuster_id'     => 'sometimes|nullable|integer|exists:banks_db.loss_adjusters,id',
            'insurer_id'           => 'sometimes|nullable|integer|exists:banks_db.insurers,id',
            'sent_to_acepta'       => 'sometimes|nullable|boolean',
            'resolution'           => 'sometimes|nullable|string',

            /* ─────── Datos generales ─────── */
            'property_address'     => 'sometimes|nullable|string|max:255',
            'property_type'        => 'sometimes|nullable|string|in:Casa,Departamento,Otro',
            'is_duplicated'        => 'sometimes|nullable|boolean',

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
            'document_signing_date'  => 'sometimes|nullable|date',

            /* ─────── Identificadores externos ─────── */
            'bank_service_number'  => 'sometimes|nullable|string|max:50',
            'accident_number'      => 'sometimes|nullable|string|max:50',

            /* ─────── Montos ─────── */
            'approved_amount'      => 'sometimes|nullable|numeric|min:0',
            'uf_approved'          => 'sometimes|nullable|numeric|min:0',
            'advisory_amount'      => 'sometimes|nullable|numeric|min:0',
            'amount_paid'          => 'sometimes|nullable|numeric|min:0',
            'amount_owed'          => 'sometimes|nullable|numeric|min:0',
            'amount_owed_including_vat' => 'sometimes|nullable|numeric|min:0',

            /* ─────── Estado de pago ─────── */
            'payment_status'       => 'sometimes|nullable|string|max:50',
        ];
    }

    /**
     * Mensajes de error en español.
     */
    public function messages(): array
    {
        return [
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.integer'  => 'El campo :attribute debe ser un número entero.',
            '*.numeric'  => 'El campo :attribute debe ser numérico.',
            '*.string'   => 'El campo :attribute debe ser texto.',
            '*.boolean'  => 'El campo :attribute debe ser verdadero o falso.',
            '*.date'     => 'El campo :attribute debe ser una fecha válida (AAAA-MM-DD).',
            '*.in'       => 'El valor seleccionado para :attribute no es válido.',
            '*.max'      => 'El campo :attribute no puede superar :max caracteres.',
            '*.min'      => 'El campo :attribute debe ser al menos :min.',
            '*.exists'   => 'El elemento seleccionado en :attribute no existe.',
        ];
    }

    /**
     * Alias legibles para cada atributo.
     */
    public function attributes(): array
    {
        return [
            // Relaciones
            'bank_id'          => 'banco',
            'customer_id'      => 'cliente',
            'agreement_id'     => 'convenio',
            'commune_id'       => 'comuna',
            'accident_type_id' => 'tipo de siniestro',
            'agent_id'         => 'agente',
            'consultant_id'    => 'consultor',
            'assigned_user'    => 'usuario asignado',
            'created_by'       => 'creado por',
            'loss_adjuster_id' => 'liquidador',
            'insurer_id'       => 'aseguradora',

            // Datos generales
            'property_address' => 'dirección de la propiedad',
            'property_type'    => 'tipo de propiedad',
            'is_duplicated'    => 'duplicado',
            'sent_to_acepta'   => 'enviado a Acepta',

            // Fechas
            'complaint_date'         => 'fecha de denuncio',
            'date_of_loss'           => 'fecha del siniestro',
            'inspection_date'        => 'fecha de visita',
            'budget_sending_date'    => 'fecha de envío de presupuesto',
            'contestation_date'      => 'fecha de impugnación',
            'settlement_report_date' => 'fecha de informe de liquidación',
            'probable_payment_date'  => 'fecha probable de pago',
            'collection_date'        => 'fecha de cobranza',
            'online_collection_date' => 'fecha de cobranza online',

            // Identificadores externos
            'bank_service_number' => 'número de servicio del banco',
            'accident_number'     => 'número de siniestro',

            // Montos
            'approved_amount' => 'monto aprobado',
            'uf_approved'     => 'UF aprobadas',
            'advisory_amount' => 'monto de asesoría',
            'amount_paid'     => 'monto pagado',
            'amount_owed'     => 'monto adeudado',

            // Estado de pago
            'payment_status'  => 'estado de pago',
        ];
    }
}
