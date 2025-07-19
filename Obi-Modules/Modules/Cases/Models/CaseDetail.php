<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Model;

class CaseDetail extends Model
{
    protected $connection   = 'cases_db';
    protected $table        = 'v_cases_details';
    public    $incrementing = false;
    public    $timestamps   = false;

    // Opcional: especifica las columnas que quieres exponer
    // protected $visible = [ 'id', 'state', 'code', 'customer_label', ... ];
}
