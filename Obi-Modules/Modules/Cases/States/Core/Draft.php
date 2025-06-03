<?php

namespace Modules\Cases\States\Core;

class Draft extends CaseEntityState
{
    public static function label(): string
    {
        return 'Borrador';
    }
}