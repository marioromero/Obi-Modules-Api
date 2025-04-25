<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'email_templates';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'user_id',
        'content',
    ];

    /** RELACIONES EXTERNAS **/

    // Relación de EmailTemplate con User (Users módulo)
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'user_id');
    }
}
