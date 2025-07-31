<?php

namespace Modules\Customers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Support\Traits\DeletionStrategies;

class Tag extends Model
{
    use HasFactory;
    use DeletionStrategies;

    protected $connection = 'customers_db';
    protected $table      = 'tags';
    public $timestamps = false;

    /** Campos rellenables */
    protected $fillable = [
        'name',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
