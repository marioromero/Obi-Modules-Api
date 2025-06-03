<?php

namespace Modules\Cases\States\Traro;

use Modules\Cases\States\Core\CaseEntityState;

class Programacion extends CaseEntityState
{
    public static function label(): string
    {
        return 'Programacion';
    }
}