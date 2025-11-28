<?php

namespace Modules\Cases\Observers;

use Modules\Cases\Models\CaseEntity;
use Modules\Cases\Support\CasesCache;

class CaseEntityObserver
{
    /**
     * Cuando se crea o actualiza un caso.
     */
    public function saved(CaseEntity $case): void
    {
        // Refresca solo este caso en el cache
        CasesCache::syncOne($case->id);
    }

    /**
     * Cuando se elimina un caso (soft delete o hard delete).
     */
    public function deleted(CaseEntity $case): void
    {
        // Asegura que desaparezca del cache
        CasesCache::syncOne($case->id);
    }
}
