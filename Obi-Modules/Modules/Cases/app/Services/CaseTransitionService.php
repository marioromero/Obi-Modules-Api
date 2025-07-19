<?php

namespace Modules\Cases\app\Services;

use Modules\Cases\Models\CaseEntity;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;

class CaseTransitionService
{
    /**
     * Valida y ejecuta transición usando transitionToWithComments().
     *
     * @throws ValidationException si la transición es ilegal
     *                             o faltan campos al avanzar.
     */
    public function transition(
        CaseEntity $case,
        string     $nextState,
        ?string    $comments = null
    ): CaseEntity
    {
        $current = class_basename($case->state);  // p.ej. "Ingreso"

        /* 1️⃣  Verificar que el salto esté permitido (ida o vuelta) */
        $map = config('modules.Cases.CaseEntity_states.transitions', []);

        // obtener array destino cuyo origen coincide por basename
        $allowed = collect($map)
            ->first(fn ($tos, $fromFqn) =>
                class_basename($fromFqn) === $current
            ) ?? [];

        if (! in_array($nextState, $allowed, true)) {
            throw ValidationException::withMessages([
                'next_state' =>
                    "No se puede pasar de $current a " . class_basename($nextState),
            ]);
        }

        /* 2️⃣  Si es avance, chequear campos de TODOS los pasos previos */
        $order      = config('modules.Cases.CaseEntity_states.states');   // lista ordenada
        $idxCurrent = array_search($current, $order, true);
        $idxNext    = array_search(class_basename($nextState), $order, true);

        if ($idxNext > $idxCurrent) {                // SOLO al avanzar
            $reqCfg  = config('modules.Cases.CaseEntity_required', []);
            $steps   = array_slice($order, 0, $idxCurrent + 1);
            $missing = [];

            foreach ($steps as $step) {
                foreach ($reqCfg[$step] ?? [] as $field) {
                    if (empty($case->{$field})) {
                        $missing[$step][] = $field;
                    }
                }
            }

            if ($missing) {
                throw ValidationException::withMessages([
                    'missing' => 'Faltan: ' . implode(', ', Arr::flatten($missing)),
                ]);
            }
        }

        /* 3️⃣  Ejecutar helper existente y devolver modelo actualizado */
        return $case
            ->transitionToWithComments($nextState, $comments)
            ->refresh();
    }
}
