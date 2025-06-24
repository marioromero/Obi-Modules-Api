<?php

namespace Modules\Cases\app\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaseEntityResource extends JsonResource
{

 public function toArray($request): array
    {
        return [
            'code'                   => $this->code,
            'customer_id'            => $this->customer_id,
            'created_at'             => $this->created_at,
            'bank_id'                => $this->bank_id,
            'property_address'       => $this->property_address,
            'accident_type_id'       => $this->accident_type_id,
            'agent_id'               => $this->agent_id,
            'complaint_date'         => $this->complaint_date,
            'date_of_loss'           => $this->date_of_loss,
            'inspection_date'        => $this->inspection_date,
            'loss_adjuster_id'       => $this->loss_adjuster_id,
            'budget_sending_date'    => $this->budget_sending_date,
            'settlement_report_date' => $this->settlement_report_date,
            'approved_amount'        => $this->approved_amount,
            'advisory_amount'        => $this->advisory_amount,
            'probable_payment_date'  => $this->probable_payment_date,
            'collection_date'        => $this->collection_date,
            'amount_paid'            => $this->amount_paid,
            'amount_owed'            => $this->amount_owed,
        ];
    }
}
