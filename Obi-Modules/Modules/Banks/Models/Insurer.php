<?php

namespace Modules\Banks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurer extends Model
{
    use HasFactory;

    protected $connection = 'banks_db';
    protected $table = 'insurers';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_visible',
        'bank_id',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de Insurer con Bank (un Insurer pertenece a un Bank)
    //Cascade
    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    // Relación de Insurer con LossAdjuster (un Insurer tiene muchos LossAdjusters)
    //Cascade
    public function lossAdjusters()
    {
        return $this->hasMany(LossAdjuster::class, 'insurer_id');
    }
}
