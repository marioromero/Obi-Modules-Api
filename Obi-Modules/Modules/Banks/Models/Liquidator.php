<?php

namespace Modules\Banks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Banks\Database\Factories\LiquidatorFactory;

class Liquidator extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): LiquidatorFactory
    // {
    //     // return LiquidatorFactory::new();
    // }
}
