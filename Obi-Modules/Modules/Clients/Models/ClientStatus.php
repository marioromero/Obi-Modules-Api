<?php

namespace Modules\Clients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Clients\Database\Factories\ClientStatusFactory;

class ClientStatus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): ClientStatusFactory
    // {
    //     // return ClientStatusFactory::new();
    // }
}
