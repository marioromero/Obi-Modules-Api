<?php

namespace Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDetail extends Model
{
    protected $connection = 'customers_db';
    protected $table      = 'v_customers_details';
    public    $incrementing = false;   // no hay PK autoincrement
    public    $timestamps   = false;
}
