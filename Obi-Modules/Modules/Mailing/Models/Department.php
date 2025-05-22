<?php

namespace Modules\Mailing\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'departments';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
    ];
    
    public function emailTemplates()   // 1️⃣2️⃣  Department → EmailTemplate
    {
        return $this->hasMany(\Modules\Mailing\Models\EmailTemplate::class, 'department_id');
    }
}

