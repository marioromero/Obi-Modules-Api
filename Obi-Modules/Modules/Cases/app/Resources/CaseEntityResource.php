<?php

namespace Modules\Cases\app\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CaseEntityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            /* --- Identificación y estado --- */
            'id'                   => $this->id,
            'code'                 => $this->code,
            'state'                => $this->state,
            'overall_status'       => $this->overall_status,
            'created_at'           => $this->created_at,

            /* --- Cliente y ubicación --- */
            'customer_id'            => $this->customer_id,
            'customer_name'          => $this->customer_name,
            'customer_dni'           => $this->customer_dni,
            'customer_address'       => $this->customer_address,
            'customer_commune_name'  => $this->customer_commune_name,
            'property_address'       => $this->property_address,
            'commune_id'             => $this->commune_id,
            'commune_name'           => $this->commune_name,

            /* --- Catálogos (IDs + nombres) --- */
            'priority_id'         => $this->priority_id,
            'priority_name'       => $this->priority_name,
            'agreement_id'        => $this->agreement_id,
            'agreement_name'      => $this->agreement_name,
            'accident_type_id'    => $this->accident_type_id,
            'accident_type_name'  => $this->accident_type_name,
            'bank_id'             => $this->bank_id,
            'bank_name'           => $this->bank_name,
            'insurer_id'          => $this->insurer_id,
            'insurer_name'        => $this->insurer_name,
            'loss_adjuster_id'    => $this->loss_adjuster_id,
            'loss_adjuster_name'  => $this->loss_adjuster_name,

            /* --- Usuarios (IDs + nombres) --- */
            'consultant_id'       => $this->consultant_id,
            'consultant_name'     => $this->consultant_name,
            'assigned_user'       => $this->assigned_user,
            'assigned_user_name'  => $this->assigned_user_name,
            'created_by'          => $this->created_by,
            'created_by_name'     => $this->created_by_name,
            'agent_id'            => $this->agent_id,     // viene de customers.assigned_agent
            'agent_name'          => $this->agent_name,

            /* --- Fechas de proceso --- */
            'date_of_loss'            => $this->date_of_loss,
            'inspection_date'         => $this->inspection_date,
            'document_signing_date'   => $this->document_signing_date,
            'complaint_date'          => $this->complaint_date,
            'budget_sending_date'     => $this->budget_sending_date,
            'settlement_report_date'  => $this->settlement_report_date,
            'probable_payment_date'   => $this->probable_payment_date,
            'collection_date'         => $this->collection_date,
            'online_collection_date'  => $this->online_collection_date,

            /* --- Números/identificadores externos --- */
            'accident_number'     => $this->accident_number,
            'bank_service_number' => $this->bank_service_number,

            /* --- Montos --- */
            'approved_amount'     => $this->approved_amount,
            'advisory_amount'     => $this->advisory_amount,
            'amount_paid'         => $this->amount_paid,
            'amount_owed'         => $this->amount_owed,
            'uf_approved'         => $this->uf_approved,

            /* --- Metadatos del caso --- */
            'is_duplicated'       => (bool) $this->is_duplicated,
            'description'         => $this->description,
            'resolution'          => $this->resolution,
            'property_type'       => $this->property_type,
            'contestation_date'   => $this->contestation_date,
            'schedule_message_sent'      => (bool) ($this->schedule_message_sent ?? false),
            'schedule_message_confirmed' => (bool) ($this->schedule_message_confirmed ?? false),
            'schedule_inspection_time'   => $this->schedule_inspection_time,

            /* --- Sub-estados del flujo --- */
            'signature_status'    => $this->signature_status,
            'denounce_status'     => $this->denounce_status,
            'scheduling_status'   => $this->scheduling_status,
            'visit_status'        => $this->visit_status,
            'budget_status'       => $this->budget_status,
            'decision_status'     => $this->decision_status,
            'payment_status'      => $this->payment_status,

            /* --- Último cambio (step-log snapshot) --- */
            'last_state_change_user_id'   => $this->last_state_change_user_id,
            'last_state_change_user_name' => $this->last_state_change_user_name,
            'last_state_change_at'        => $this->last_state_change_at,

             /* --- Notificaciones (desde cf_last en la vista) --- */
            'active_notifications' => (bool) ($this->active_notifications ?? false),
            'available_notifications' => (bool) ($this->available_notifications ?? false),
        ];
    }
}
