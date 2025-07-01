<?php

namespace Modules\Cases\States\Traro;

use Modules\Cases\States\Core\CaseEntityState;

class Denuncio extends CaseEntityState
{
    public static function label(): string
    {
        return 'Denuncio';
    }
}