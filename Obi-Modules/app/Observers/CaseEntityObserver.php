<?php

namespace App\Observers;

use Modules\Cases\Models\CaseEntity;

class CaseEntityObserver
{
    /**
     * Cuando el modelo se “guarde” (create/update)…
     */
    public function saved(CaseEntity $case): void
    {
        // Re-calcular overall_status si cambió algo relevante
        $dirty = $case->getChanges();

        $shouldRecalc = array_intersect_key($dirty, array_flip([
            'state', 'payment_status', 'decision_result',
            'visit_status', 'signature_status', 'budget_status',
        ]));

        if ($shouldRecalc) {
            $case->overall_status = $case->calculateOverallStatus();
            // saveQuietly para no disparar un loop infinito de eventos
            $case->saveQuietly();
        }
    }
}
