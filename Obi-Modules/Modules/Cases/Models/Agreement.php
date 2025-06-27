<?php

namespace Modules\Cases\Models;

use Modules\Core\app\Support\Traits\DeletionStrategies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Agreement extends Model
{
    use DeletionStrategies;
    use HasFactory;

    // ❌ Desactiva timestamps
    public $timestamps = false;

    // 📝 Asignable solo el nombre
    protected $fillable = [
        'name',
    ];

    public function cases()
    {
        return $this->hasMany(CaseEntity::class, 'agreement_id');
    }

}
