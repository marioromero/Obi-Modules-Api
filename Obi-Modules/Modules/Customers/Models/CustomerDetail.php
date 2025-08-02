<?php

namespace Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDetail extends Model
{
    protected $connection   = 'customers_db';
    protected $table        = 'v_customers_details';
    public    $incrementing = false;  // la vista no tiene PK autoincrement
    public    $timestamps   = false;

    /* ─────── Casts ─────── */
    protected $casts = [
        'tags' => 'array',   // JSON ⇄ array
    ];
}
