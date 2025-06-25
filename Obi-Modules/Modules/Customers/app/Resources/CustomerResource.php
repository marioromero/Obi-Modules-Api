<?php

namespace Modules\Customers\app\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'lastname' => $this->lastname,
            'dni'      => $this->dni,
            'email'    => $this->email,
            'phone'    => $this->phone,
        ];
    }
}
