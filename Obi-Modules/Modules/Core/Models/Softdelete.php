<?php

namespace Modules\Core\Models;

use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Core\Database\Factories\SoftdeleteFactory;

class Softdelete extends Model
{
    use DeletionStrategies;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): SoftdeleteFactory
    // {
    //     // return SoftdeleteFactory::new();
    // }
}
