<?php

namespace Modules\Customers\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'customers_db';
    protected $table = 'customers';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'lastname',
        'dni',
        'username',
        'password',
        'email',
        'address',
        'phone',
        'phone2',
        'gender',
        'marital_status',
        'occupation',
        'case_status_id',
        'commune_id',
        'user_id',
        'bank_id',
    ];

    // Relación de Customer con CustomerStatus (un Customer pertenece a un CustomerStatus)
    //No Action
    public function customerStatus()
    {
        return $this->belongsTo(CustomerStatus::class, 'case_status_id');
    }

    // Relación de Customer con Commune (un Customer pertenece a una Commune) [FK externa]
    public function commune()
    {
        return $this->belongsTo(\Modules\Geography\Models\Commune::class, 'commune_id');
    }

    // Relación de Customer con User (un Customer puede pertenecer a un User) [FK externa]
    //Set Null
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'user_id');
    }
    public function cases()          // 2️⃣  CaseEntity → Customer
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'customer_id');
    }
    //Cascade
    public function customerDetails() // 8️⃣  CustomerDetail → Customer
    {
        return $this->hasMany(\Modules\Mailing\Models\CustomerDetail::class, 'customer_id');
    }
    // Relación de Customer con Bank (un Customer pertenece a un Bank) [FK externa]
    public function bank()
    {
        return $this->belongsTo(\Modules\Banks\Models\Bank::class, 'bank_id');
    }
}

