<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDetail extends Model
{
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'customer_detail';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'customer_set_id',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de CustomerDetail con CustomersSet (un CustomerDetail pertenece a un CustomersSet)
    public function customersSet()
    {
        return $this->belongsTo(CustomersSet::class, 'customer_set_id');
    }

    /** RELACIONES EXTERNAS **/

    // Relación de CustomerDetail con Customer (Customers módulo)
    //Cascade
    public function customer()
    {
        return $this->belongsTo(\Modules\Customers\Models\Customer::class, 'customer_id');
    }
}
