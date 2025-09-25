<?php

namespace Modules\Cases\app\Services;

use Modules\Cases\Models\CaseEntity;
use Illuminate\Validation\ValidationException;

class CaseTransitionService
{
    /**
     * Valida y ejecuta transición usando transitionToWithComments().
     *
     * @param  CaseEntity  $case
     * @param  string      $nextState  Puede ser basename ("Liquidacion") o FQCN ("Modules\Cases\States\Traro\Liquidacion")
     * @param  string|null $comments
     * @param  int|null    $userId
     * @throws ValidationException
     * @return CaseEntity
     */
    public function transition(
        CaseEntity $case,
        string     $nextState,
        ?string    $comments = null,
        ?int       $userId   = null
    ): CaseEntity {
        $currentFqn  = $case->state::class;
        $currentBase = class_basename($currentFqn);

        // 0) Normalizar nextState: aceptar basename o FQCN
        if (! str_contains($nextState, '\\')) {
            // Lo convierto a FQCN en el namespace Traro
            $nextState = "Modules\\Cases\\States\\Traro\\{$nextState}";
        }
        $nextBase = class_basename($nextState);

        /* 1) Verificar que la transición esté permitida (usando la clave correcta 'cases.*') */
        $map = config('cases.CaseEntity_states.transitions', []);
        // Tomar directamente las transiciones desde el FQCN actual (más preciso que filtrar por basename)
        $allowedFqn = $map[$currentFqn] ?? [];
        if (! in_array($nextState, $allowedFqn, true)) {
            throw ValidationException::withMessages([
                'next_state' => "No se puede pasar de {$currentBase} a {$nextBase}",
            ]);
        }

        /* 2) Si es avance (next idx > current idx), chequear campos requeridos previos (si los configuraste) */
        $order      = config('cases.CaseEntity_states.states', []);
        $idxCurrent = array_search($currentBase, $order, true);
        $idxNext    = array_search($nextBase,   $order, true);

        if ($idxCurrent === false || $idxNext === false) {
            // Config inconsistente o estado desconocido
            throw ValidationException::withMessages([
                'state' => "Estados no configurados correctamente en 'cases.CaseEntity_states.states'.",
            ]);
        }

        /* 3) Ejecutar helper y devolver modelo actualizado */
        return $case
            ->transitionToWithComments(
                $nextState,
                $comments,
                $userId
            )
            ->refresh();
    }
}
