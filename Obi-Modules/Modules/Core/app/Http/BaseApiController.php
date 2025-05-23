<?php

namespace Modules\Core\app\Http;

use Illuminate\Routing\Controller;
use Modules\Core\app\Support\DTO\ServiceResponseDTO;
use Modules\Core\app\Support\Traits\ApiResponse;

class BaseApiController extends Controller
{
    use ApiResponse;

    protected function respondService(ServiceResponseDTO $response)
    {
        if ($response->success) {
            return $this->success(
                $response->data,
                $response->message,
                $response->code
            );
        }

        return $this->error(
            $response->message,
            $response->code,
        );
    }
}

