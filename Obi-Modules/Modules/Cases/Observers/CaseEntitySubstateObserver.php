<?php

namespace Modules\Cases\Observers;

use Modules\Cases\Models\CaseEntity;
use Modules\Cases\Models\CaseEntityStepLog;

class CaseEntitySubstateObserver
{
    public function saved(CaseEntity $entity): void
    {
        $dirty = $entity->getChanges();
        $subKeys = array (
  0 => 'signature_status',
  1 => 'denounce_status',
  2 => 'scheduling_status',
  3 => 'visit_status',
  4 => 'budget_status',
  5 => 'decision_status',
  6 => 'payment_status',
);
        $intersect = array_intersect_key($dirty, array_flip($subKeys));

        foreach ($intersect as $key => $new) {
            $original = $entity->getOriginal($key) ?? null;


            CaseEntityStepLog::create([
                'case_id' => $entity->id,
                'from_state'  => null,
                'to_state'    => null,
                'from_sub'    => $original,
                'to_sub'      => $new,
                'type'        => 'sub_state',
                'user_id'     => auth()->id(),
                'payload'     => json_encode([$key => $new]),
                'comments'    => null,
            ]);
        }
    }
}