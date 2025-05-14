<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
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
