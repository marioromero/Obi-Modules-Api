<?php

namespace Modules\Cases\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'agreement_id' => $this->input('agreement_id', 1),
        ]);
    }

    public function rules(): array
    {
        return [

             /* ─────── Relaciones ─────── */
            'customer_id'          => 'nullable|integer|exists:customers_db.customers,id',
            'agreement_id'         => 'nullable|integer|exists:cases_db.agreements,id',
            'commune_id'           => 'nullable|integer|exists:geography_db.communes,id',
            'accident_type_id'     => 'nullable|integer|exists:cases_db.accident_types,id',
            'agent_id'             => 'nullable|integer|exists:traro_db.users,id',
            'loss_adjuster_id'     => 'nullable|integer|exists:banks_db.loss_adjusters,id',
            'insurer_id'           => 'nullable|integer|exists:banks_db.insurers,id',
            'consultant_id'        => 'nullable|integer|exists:traro_db.users,id',
            'bank_id'              => 'nullable|integer|exists:banks_db.banks,id',
            'assigned_user'        => 'nullable|integer|exists:traro_db.users,id',
            'created_by'           => 'nullable|integer|exists:traro_db.users,id',

            /* ─────── Datos generales del siniestro ─────── */
            'property_address'     => 'required|string|max:255',
            'property_type'        => 'required|string|in:Casa,Departamento,Otro',
            'is_duplicated'        => 'required|boolean',
            'comments_programming' => 'nullable|string',

            /* ─────── Fechas ─────── */
            'complaint_date'       => 'nullable|date',
            'date_of_loss'         => 'nullable|date',
            'inspection_date'      => 'nullable|date',
            'budget_sending_date'  => 'nullable|date',
            'contestation_date'    => 'nullable|date',
            'settlement_report_date' => 'nullable|date',
            'probable_payment_date'  => 'nullable|date',
            'collection_date'        => 'nullable|date',
            'online_collection_date' => 'nullable|date',

            /* ─────── Identificadores de terceros / externos ─────── */
            'bank_service_number'  => 'nullable|string|max:50',
            'accident_number'      => 'nullable|string|max:50',

            /* ─────── Montos ─────── */
            'approved_amount'      => 'nullable|numeric|min:0',
            'uf_approved'          => 'nullable|numeric|min:0',
            'advisory_amount'      => 'nullable|numeric|min:0',
            'amount_paid'          => 'nullable|numeric|min:0',
            'amount_owed'          => 'nullable|numeric|min:0',

            /* ─────── Estado de pago ─────── */
            'payment_status'       => 'nullable|string|max:50',
        ];
    }
}
