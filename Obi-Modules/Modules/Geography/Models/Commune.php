<?php

namespace Modules\Geography\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'geography_db';
    protected $table = 'communes';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'province_id',
    ];

    // Relación de Commune con Province (una Commune pertenece a una Province)
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }
    public function cases()          // 1️⃣  CaseEntity → Commune
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'commune_id');
    }

    public function customers()      // 6️⃣  Customer → Commune
    {
        return $this->hasMany(\Modules\Customers\Models\Customer::class, 'commune_id');
    }

    public function users()          // 6️⃣  User → Commune
    {
        return $this->hasMany(\Modules\Users\Models\User::class, 'commune_id');
    }
}

