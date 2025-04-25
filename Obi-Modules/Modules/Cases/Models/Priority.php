<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cases\Models\CaseEntity;

class Priority extends Model
{
    use HasFactory;

    protected $connection = 'cases_db';
    protected $table = 'priorities';
    public $timestamps = false;

    protected $fillable = ['name'];

    // Relación de Priority con CaseEntity (una prioridad tiene muchos casos)
    public function cases()
    {
        return $this->hasMany(CaseEntity::class, 'priority');
    }
}
