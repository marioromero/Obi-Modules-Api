<?php

namespace Modules\Cases\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'cases_db';
    protected $table = 'comments';
    public $timestamps = false;

    protected $fillable = [
        'content',
        'created_at',
        'case_id',
        'user_id',
        'response_from',
    ];

    /** RELACIONES INTERNAS (dentro de Cases) **/

    // Relación de Comment con CaseEntity (un Comment pertenece a un CaseEntity)
    public function case()
    {
        return $this->belongsTo(CaseEntity::class, 'case_id');
    }

    // Relación de Comment consigo mismo (un Comment puede responder a otro Comment)
    //Cascade
    public function parentComment()
    {
        return $this->belongsTo(Comment::class, 'response_from');
    }

    // Relación de Comment con otros Comments que respondan a él
    public function responses()
    {
        return $this->hasMany(Comment::class, 'response_from');
    }

    /** RELACIONES EXTERNAS (a otros módulos) **/

    // Relación de Comment con Users::User
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'user_id');
    }
}
