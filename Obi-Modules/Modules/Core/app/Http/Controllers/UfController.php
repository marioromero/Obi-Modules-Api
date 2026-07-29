<?php

namespace Modules\Core\app\Http\Controllers;

use Modules\Core\app\Http\BaseApiController;
use Modules\Core\app\Services\MindicadorService;
use Illuminate\Http\Request;

class UfController extends BaseApiController
{
    public function __construct(
        private readonly MindicadorService $mindicadorService
    ) {}

    public function getByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:d-m-Y',
        ]);

        return $this->respondService(
            $this->mindicadorService->getUfByDate($request->input('date'))
        );
    }
}
