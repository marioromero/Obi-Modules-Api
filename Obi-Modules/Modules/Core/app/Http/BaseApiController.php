<?php

namespace Modules\Core\app\Http;

use Illuminate\Routing\Controller;
use Modules\Core\app\Support\Traits\ApiResponse;

class BaseApiController extends Controller
{
    use ApiResponse;
}

