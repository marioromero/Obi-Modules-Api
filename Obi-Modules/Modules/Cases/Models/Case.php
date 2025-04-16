<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Cases\Database\Factories\CaseFactory;

class Case extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): CaseFactory
    // {
    //     // return CaseFactory::new();
    // }
}
