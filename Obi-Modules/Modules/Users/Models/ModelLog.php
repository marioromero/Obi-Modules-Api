<?php

namespace Modules\Users\Models;

use Modules\Core\app\Http\BaseApiController;
use Modules\Core\app\Support\Traits\DeletionStrategies;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModelLog extends BaseApiController
{
    use DeletionStrategies;
    use HasFactory;

    protected $table = 'model_logs';
    public $timestamps = false;
    protected $connection = 'users_db';

    protected $fillable = ['name'];

    public function logs()
    {
        return $this->hasMany(UserLog::class, 'model_id');
    }
}
