<?php

namespace Modules\Banks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $connection = 'banks_db';
    protected $table = 'banks';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_visible',
    ];

    // Relación de Bank con Insurer (un Bank tiene muchos Insurers)
    //Cascade
    public function insurers()
    {
        return $this->hasMany(Insurer::class, 'bank_id');
    }
}
