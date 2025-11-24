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
    public $timestamps = true;

    protected $fillable = [
        'name',
        'lastname',
        'full_name',
        'dni',
        'username',
        'password',
        'serial_number',
        'email',
        'address',
        'phone',
        'phone2',
        'gender',
        'marital_status',
        'occupation',
        'nationality',
        'commune_id',
        'assigned_agent',
        'is_enabled',
        'tags',
        'comments',
    ];

    protected $casts = [
        'tags' => 'array',   // JSON ⇄ array automáticamente
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    // Relación de Customer con Commune (un Customer pertenece a una Commune) [FK externa]
    public function commune()
    {
        return $this->belongsTo(\Modules\Geography\Models\Commune::class, 'commune_id');
    }

    // Relación de Customer con User asignado (antes user_id) [FK externa]
    public function assignedAgent()
    {
        return $this->belongsTo(\Modules\Users\Models\TraroUser::class, 'assigned_agent');
    }

    // Alias para compatibilidad: mantiene $customer->user
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'assigned_agent');
    }

    public function cases()          // 2️⃣  CaseEntity → Customer
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'customer_id');
    }

    // Cascade
    public function customerDetails() // 8️⃣  CustomerDetail → Customer
    {
        return $this->hasMany(\Modules\Mailing\Models\CustomerDetail::class, 'customer_id');
    }

    /**
     * Boot: antes de crear, añade las etiquetas activas con enabled=false
     */
    protected static function booted(): void
    {
        // excluye los registros con softdeleted = 1
        static::addGlobalScope('exclude_softdeleted', function ($query) {
            $query->where('softdeleted', 0);
        });

        // setea los tags por defecto al crear un cliente
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

    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutGlobalScope('exclude_softdeleted')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
