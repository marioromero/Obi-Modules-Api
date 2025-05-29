<?php

namespace Modules\Banks\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'banks_db';
    protected $table = 'banks';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_visible',
    ];

    // Relación de Bank con Insurer (un Bank tiene muchos Insurers)
    //Cascade
    public function insurers()
    {
        return $this->hasMany(Insurer::class, 'bank_id');
    }
    // Relación de Bank con Customer (un Bank tiene muchos Customers)
    public function customers()
    {
        return $this->hasMany(\Modules\Customers\Models\Customer::class, 'bank_id');
    }

}

