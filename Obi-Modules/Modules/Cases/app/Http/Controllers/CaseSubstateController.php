<?php

namespace Modules\Cases\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\app\Http\BaseApiController;   
use Modules\Cases\Models\CaseEntity;
use Modules\Cases\app\Services\CaseSubstateService;

class CaseSubstateController extends BaseApiController
{
    /**
     * Cambia el sub-estado del caso usando transitionSubstate().
     *
     * POST /cases/{case}/substate
     * Body: { "value": "mandato pendiente", "comment": "opc.", "user_id": 0 }
     */
    public function update(Request $request, CaseEntity $case)
    {
        $value   = $request->input('value');
        $comment = $request->input('comment');            // null permitido
        $userId  = $request->input('user_id', null);      // null = acción de sistema

        app(CaseSubstateService::class)
            ->set($case, $value, $comment, $userId);

        return $this->success(null, 'Sub-estado actualizado correctamente');
    }
}
