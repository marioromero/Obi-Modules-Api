<?php

namespace Modules\Reports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $connection = 'reports_db';
    protected $table = 'reports';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'content',
        'is_shared',
        'user_id',
    ];

    /** RELACIONES EXTERNAS **/

    // Relación de Report con Users::User (un Report pertenece a un User)
    //Set Null
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'user_id');
    }
}
