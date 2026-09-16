<?php

namespace Modules\Core\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\app\Http\BaseApiController;
use Modules\Core\app\Services\UfService;

class UfController extends BaseApiController
{
    public function __construct(
        private readonly UfService $ufService
    ) {}

    public function getByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:d-m-Y',
        ]);

        return $this->respondService(
            $this->ufService->getUfByDate($request->input('date'))
        );
    }
}
