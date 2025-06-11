<?php

namespace Modules\Configurations\Models;

use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Configurations\Database\Factories\TypeFactory;

class Type extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'configurations_db';
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
