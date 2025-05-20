<?php

namespace Modules\Users\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'users_db';
    protected $table = 'types';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    // Relación de Type con Configuration (un Type tiene muchas Configurations)
    public function configurations()
    {
        return $this->hasMany(Configuration::class, 'type_id');
    }
}
