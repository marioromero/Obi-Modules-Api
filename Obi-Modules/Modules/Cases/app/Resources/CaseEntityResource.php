<?php

namespace Modules\Cases\app\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseEntityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            /* ────────── Datos básicos del caso ────────── */
            'id'               => $this->id,
            'code'             => $this->code,
            'state'            => $this->state,

            /* ────────── Cliente (IDs + nombres) ────────── */
            'customer_id'          => $this->customer_id,
            'customer_name'        => $this->customer_name,
            'customer_dni'         => $this->customer_dni,
            'customer_address'     => $this->customer_address,
            'customer_commune_name'=> $this->customer_commune_name,

            /* ────────── Catálogos ────────── */
            'bank_id'          => $this->bank_id,
            'bank_name'        => $this->bank_name,
            'insurer_id'       => $this->insurer_id,
            'insurer_name'     => $this->insurer_name,
            'loss_adjuster_id' => $this->loss_adjuster_id,
            'loss_adjuster_name' => $this->loss_adjuster_name,
            'accident_type_id' => $this->accident_type_id,
            'accident_type_name' => $this->accident_type_name,
            'agreement_id'     => $this->agreement_id,
            'agreement_name'   => $this->agreement_name,
            'priority_id'      => $this->priority_id,
            'priority_name'    => $this->priority_name,

            /* ────────── Usuarios ────────── */
            'agent_id'             => $this->agent_id,
            'agent_name'           => $this->agent_name,
            'consultant_id'        => $this->consultant_id,
            'consultant_name'      => $this->consultant_name,
            'assigned_user'        => $this->assigned_user,
            'assigned_user_name'   => $this->assigned_user_name,

            /* ────────── Fechas y montos ────────── */
            'created_at'           => $this->created_at,
            'property_address'     => $this->property_address,
            'date_of_loss'         => $this->date_of_loss,
            'inspection_date'      => $this->inspection_date,
            'complaint_date'       => $this->complaint_date,
            'budget_sending_date'  => $this->budget_sending_date,
            'settlement_report_date'=> $this->settlement_report_date,
            'probable_payment_date'=> $this->probable_payment_date,
            'collection_date'      => $this->collection_date,

            'approved_amount'      => $this->approved_amount,
            'advisory_amount'      => $this->advisory_amount,
            'amount_paid'          => $this->amount_paid,
            'amount_owed'          => $this->amount_owed,
        ];
    }
}
