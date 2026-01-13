<?php

namespace Modules\Mailing\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomersSet extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'customers_sets';

    protected $fillable = [
        'name',
        'user_id',
    ];

    /** RELACIONES EXTERNAS **/

    // Relación de CustomersSet con User (Users módulo)
    //Set Null
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\TraroUser::class, 'user_id');
    }
    //Cascade
    public function emailSchedules()   // 🔟  EmailSchedule → CustomersSet
    {
        return $this->hasMany(\Modules\Mailing\Models\EmailSchedule::class, 'customer_set_id');
    }
    //Cascade
    public function customerDetails()
    {
        return $this->hasMany(\Modules\Mailing\Models\CustomerDetail::class, 'customer_set_id');
    }

    public function customers()
    {
        return $this->belongsToMany(
            \Modules\Customers\Models\Customer::class,
            'customer_detail',
            'customer_set_id',
            'customer_id'
        );
    }
}

