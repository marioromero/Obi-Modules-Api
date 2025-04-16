<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Cases\Database\Factories\CaseStatusFactory;

class CaseStatus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): CaseStatusFactory
    // {
    //     // return CaseStatusFactory::new();
    // }
}
