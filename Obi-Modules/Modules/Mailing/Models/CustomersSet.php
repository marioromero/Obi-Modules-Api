<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomersSet extends Model
{
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'customers_sets';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'user_id',
    ];

    /** RELACIONES EXTERNAS **/

    // Relación de CustomersSet con User (Users módulo)
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'user_id');
    }
    public function emailSchedules()   // 🔟  EmailSchedule → CustomersSet
    {
        return $this->hasMany(\Modules\Mailing\Models\EmailSchedule::class, 'customer_set');
    }
}
