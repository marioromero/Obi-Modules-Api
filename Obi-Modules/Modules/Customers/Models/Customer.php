<?php

namespace Modules\Customers\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;
use Modules\Customers\Models\Tag as TagModel;
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
        'nationality',
        'case_status_id',
        'commune_id',
        'user_id',
        'tags',
        'comments',
    ];

    protected $casts = [
        'tags' => 'array',   // JSON ⇄ array automáticamente
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
/**
     * Boot: antes de crear, añade las etiquetas activas con enabled=false
     */
    protected static function booted(): void
    {
        static::creating(function (self $customer) {
            if (is_null($customer->tags)) {
                $customer->tags = TagModel::where('is_active', true)
                    ->get(['name', 'color'])     
                    ->map(fn ($tag) => [
                        'name'    => $tag->name,
                        'color'   => $tag->color,
                        'enabled' => false,
                    ])
                    ->toArray();
            }
        });
    }
}

