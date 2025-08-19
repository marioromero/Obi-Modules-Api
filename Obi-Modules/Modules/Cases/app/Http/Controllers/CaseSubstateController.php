<?php

namespace Modules\Cases\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\app\Http\BaseApiController;
use Modules\Cases\Models\CaseEntity;
use Modules\Cases\app\Services\CaseSubstateService;
use Illuminate\Validation\ValidationException;
use Throwable;

class CaseSubstateController extends BaseApiController
{
    //Cambia el sub-estado del caso
    public function update(Request $request, int $id)
    {
        // Aceptar alias sin romper clientes previos
        $substate = $request->input('substate', $request->input('value'));
        $comments = $request->input('comments', $request->input('comment'));
        $userId   = $request->input('user_id');

        // Validación mínima alineada al estándar
        if ($substate === null || !is_string($substate) || trim($substate) === '') {
            return $this->error('El campo substate es requerido', 422);
        }
        if ($userId !== null && !is_numeric($userId)) {
            return $this->error('El campo user_id debe ser numérico', 422);
        }

        // Buscar caso
        $case = CaseEntity::find($id);
        if (! $case) {
            return $this->error('Caso no encontrado', 404);
        }

        try {
            app(CaseSubstateService::class)->set(
                $case,
                (string) $substate,
                $comments !== null ? (string) $comments : null,
                $userId !== null ? (int) $userId : null
            );
            return $this->success($case->refresh(), 'Sub-estado actualizado correctamente', 200);
        } catch (ValidationException $e) {
            return $this->error('Datos inválidos', 422);
        } catch (Throwable $e) {
            return $this->error($e->getMessage() ?: 'Error interno', 500);
        }
    }
}
