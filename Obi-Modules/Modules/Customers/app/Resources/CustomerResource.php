<?php

namespace Modules\Customers\app\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    
    public function toArray($request): array
    {
        return [
            // ── campos directos ──
            'id'            => $this->id,
            'name'          => $this->name,
            'lastname'      => $this->lastname,
            'dni'           => $this->dni,
            'email'         => $this->email,
            'address'       => $this->address,
            'phone'         => $this->phone,
            'phone2'        => $this->phone2,
            'gender'        => $this->gender,
            'marital_status'=> $this->marital_status,
            'occupation'    => $this->occupation,

            // ── FK (por si las necesitas) ──
            'commune_id'       => $this->commune_id,
            'case_status_id'   => $this->case_status_id,
            'user_id'          => $this->user_id,

            // ── nombres legibles ──
            'commune_name'       => optional($this->commune)->name,
            'case_status_name'   => optional($this->customerStatus)->name,
            'user_name'          => optional($this->user)->name,
        ];
    }
}
