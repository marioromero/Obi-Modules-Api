<?php

namespace Modules\Geography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Geography\Database\Factories\CommuneFactory;

class Commune extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): CommuneFactory
    // {
    //     // return CommuneFactory::new();
    // }
}
