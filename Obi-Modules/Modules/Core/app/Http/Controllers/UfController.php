<?php

namespace Modules\Core\app\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        // El valor depende de la query string. Evita que un proxy/CDN
        // reutilice la respuesta de otra fecha.
        return response()
            ->json($result, 200)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
