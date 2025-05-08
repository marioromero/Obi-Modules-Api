<?php

// Modules/Core/app/Http/BaseApiController.php
namespace Modules\Core\Http;

use Illuminate\Routing\Controller;
use Modules\Core\Support\Traits\ApiResponse;

class BaseApiController extends Controller
{
    use ApiResponse;
}
