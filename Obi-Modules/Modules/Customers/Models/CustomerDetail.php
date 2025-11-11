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

    // Excluir registros con sofdeleted = 1
    protected static function booted()
    {
        static::addGlobalScope('exclude_softdeleted', function ($query) {
            $query->where('softdeleted', 0);
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutGlobalScope('exclude_softdeleted')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
