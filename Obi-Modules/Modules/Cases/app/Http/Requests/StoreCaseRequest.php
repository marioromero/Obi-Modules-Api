<?php

namespace Modules\Cases\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /* ───── PK y claves básicas ───── */
            'code'           => 'required|string|size:12',
            'priority_id'    => 'required|integer',
            'created_at'     => 'required|date',
            'state'          => 'nullable|string|max:40',

            /* ───── Banderas y textos ───── */
            'is_duplicated'  => 'boolean',
            'description'    => 'nullable|string',
            'resolution'     => 'nullable|string',

            /* ───── Relaciones genéricas ───── */
            'customer_id'    => 'required|integer',
            'created_by'     => 'required|integer',
            'assigned_user'  => 'required|integer',
            'agent_id'       => 'required|integer',

            /* ───── Datos core del negocio ───── */
            'date_of_loss'         => 'nullable|date',
            'property_type'        => 'nullable|string|max:30',
            'contestation_date'    => 'nullable|date',

            /* Montos específicos */
            'approved_amount'      => 'nullable|integer',
            'uf_approved'          => 'nullable|numeric',
            'amount_owed'          => 'nullable|integer',
            'amount_paid'          => 'nullable|integer',

            /* Relaciones externas (banco / aseguradora / liquidadora) */
            'bank_id'         => 'nullable|integer',
            'insurer_id'      => 'nullable|integer',
            'loss_adjuster_id'=> 'nullable|integer',
        ];
    }
}
