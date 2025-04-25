<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
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
