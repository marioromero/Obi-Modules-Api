<?php

namespace Modules\Cases\app\Services;

use Illuminate\Validation\ValidationException;
use Modules\Cases\Models\CaseEntity;

class CaseSubstateService
{
    /**
     * Ejecuta transitionSubstate() validando que el valor sea permitido.
     *
     * @throws ValidationException
     */
    public function set(
        CaseEntity $case,
        string     $value,
        ?string    $comment = null,
        ?int       $userId  = null,
    ): CaseEntity {

        $cfg       = config('modules.Cases.CaseEntity_states.sub_states');
        $stateKey  = class_basename($case->state);

        if (! isset($cfg[$stateKey])) {
            throw ValidationException::withMessages([
                'value' => "El estado '$stateKey' no tiene sub-estados definidos.",
            ]);
        }
        if (! in_array($value, $cfg[$stateKey]['values'], true)) {
            throw ValidationException::withMessages([
                'value' => "Valor '$value' no permitido para $stateKey.",
            ]);
        }

        return $case->transitionSubstate($value, $userId, $comment)->refresh();
    }
}
