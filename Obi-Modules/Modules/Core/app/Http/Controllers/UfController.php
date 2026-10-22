<?php

namespace Modules\Core\app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Core\app\Http\BaseApiController;
use Modules\Core\app\Services\UfService;

class UfController extends BaseApiController
{
    public function __construct(
        private readonly UfService $ufService
    ) {}

    public function getByDate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $result = $this->ufService->getUfDashboard($validated['date']);

        return response()->json($result, 200);
    }
}
