<?php

namespace Modules\Cases\States\Core;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;
use Illuminate\Support\Arr;

abstract class CaseEntityState extends State
{
    abstract public static function label(): string;

    public static function config(): StateConfig
    {
        // 1) Intentar cargar config desde Laravel
        $cfg = config('modules.Cases.CaseEntity_states');

        // 2) Fallback a archivo si aún no está mergeado
        if (! is_array($cfg)) {
            $path = module_path('Cases', 'Config/CaseEntity_states.php');
            if (! file_exists($path)) {
                throw new \RuntimeException("No se encontró Config/CaseEntity_states.php");
            }
            $cfg = require $path;
        }

        // 3) Validar estructura mínima
        if (! isset($cfg['default'], $cfg['transitions']) || ! is_array($cfg['transitions'])) {
            throw new \InvalidArgumentException(
                "El config CaseEntity_states debe tener 'default' y 'transitions' como array"
            );
        }

        // 4) Crear StateConfig con default
        /** @var StateConfig $sc */
        $sc = parent::config()->default($cfg['default']);

        // 5) Registrar transiciones
        foreach ($cfg['transitions'] as $from => $tos) {
            if (! is_array($tos)) continue;
            foreach ($tos as $to) {
                $sc->allowTransition($from, $to);
            }
        }

        // 6) Registrar todos los estados para evitar instanciaciones inválidas
        $allStates = array_unique(array_merge(
            array_keys($cfg['transitions']),
            Arr::flatten(array_values($cfg['transitions']))
        ));
        $sc->registerState($allStates);

        return $sc;
    }

    /**
     * Alias a equals(), chequea múltiples posibles clases.
     */
    public function isAny(string ...$stateClasses): bool
    {
        foreach ($stateClasses as $st) {
            if ($this->equals($st)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Alias a equals(), para soporte legacy de is().
     */
    public function is(string $stateClass): bool
    {
        return $this->equals($stateClass);
    }
}