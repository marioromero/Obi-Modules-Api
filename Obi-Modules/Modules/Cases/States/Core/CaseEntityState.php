<?php

namespace Modules\Cases\States\Core;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class CaseEntityState extends State
{
    abstract public static function label(): string;

    public static function config(): StateConfig
    {
        $cfg = config('modules.Cases.CaseEntity_states', []);

        $stateConfig = parent::config()->default($cfg['default'] ?? Draft::class);

        foreach (($cfg['transitions'] ?? []) as $from => $tos) {
            foreach ($tos as $to) {
                $stateConfig->allowTransition($from, $to);
            }
        }
        return $stateConfig;
    }
}