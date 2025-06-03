<?php

namespace Modules\Cases\States\Core;

class Closed extends CaseEntityState
{
    public static function label(): string
    {
        return 'Cerrado';
    }
}