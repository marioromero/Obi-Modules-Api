<?php

namespace Modules\Cases\app\Http\Controllers;

use Modules\Core\app\Http\BaseApiController;
use Modules\Cases\Models\CaseEntity;
use Modules\Cases\app\Services\CaseSubstateService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class CaseSubstateController extends BaseApiController
{
    public function update(Request $request, CaseEntity $case)
    {
        // Soporte de alias para compatibilidad hacia atrás
        $input = [
            'substate' => $request->input('substate', $request->input('value')),
            'comments' => $request->input('comments', $request->input('comment')),
            'user_id'  => $request->input('user_id'),
        ];

        // Validación mínima (estándar del proyecto)
        $data = validator($input, [
            'substate' => 'required|string|min:1|max:100',
            'comments' => 'nullable|string',
            'user_id'  => 'nullable|integer|min:1',
        ])->validate();

        try {
            app(CaseSubstateService::class)->set(
                $case,
                $data['substate'],
                $data['comments'] ?? null,
                $data['user_id'] ?? null
            );

            return $this->success($case->refresh(), 'Sub-estado actualizado correctamente', 200);
        } catch (ValidationException $e) {
            return $this->error('Datos inválidos', 422);
        } catch (Throwable $e) {
            return $this->error($e->getMessage() ?: 'Error interno', 500);
        }
    }
}
