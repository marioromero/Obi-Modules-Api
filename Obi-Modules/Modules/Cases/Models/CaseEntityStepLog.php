<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Model;

class CaseEntityStepLog extends Model
{
    protected $connection = 'cases_db';
    protected $table      = 'case_step_logs';

    protected $fillable = [
        'case_id',
        'from_state', 'to_state',
        'from_sub',   'to_sub',
        'type',
        'user_id',
        'payload',
        'comments',
        'created_at',
    ];

    public $timestamps = false;
}