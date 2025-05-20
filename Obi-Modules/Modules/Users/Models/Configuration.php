<?php

namespace Modules\Users\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Configuration extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'users_db';
    protected $table      = 'configurations';
    public    $timestamps = false;

    protected $fillable = [
        'content',
        'type_id',
    ];
    //No Action
    public function type()             // 1️⃣5️⃣  Type ↔ Configuration
    {
        return $this->belongsTo(Type::class, 'type_id');
    }
}
