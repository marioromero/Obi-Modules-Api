<?php

namespace Modules\Customers\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerStatus extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'customers_db';
    protected $table = 'customer_statuses';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    // Relación de CustomerStatus con Customer (un CustomerStatus tiene muchos Customers)
    public function customers()
    {
        return $this->hasMany(Customer::class, 'case_status_id');
    }
}
