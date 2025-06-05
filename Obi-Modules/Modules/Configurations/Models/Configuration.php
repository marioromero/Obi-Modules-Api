<?php

namespace Modules\Configurations\Models;

use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Configuration extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'configurations_db';
    protected $table      = 'configurations';
    public    $timestamps = false;

    protected $fillable = [
        'content',
        'type_id',
    ];
    //No Action
    public function type()             //Type ↔ Configuration
    {
        return $this->belongsTo(Type::class, 'type_id');
    }
}
