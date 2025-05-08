<?php

namespace Modules\Core\Support\Traits;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'code'    => $code,
            'data'    => $data,
        ], $code);
    }

    protected function error(string $message, int $code = 400, mixed $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'errors'  => $errors,
        ], $code);
    }

    protected function paginated($paginator, string $message = 'OK')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'code'    => 200,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}


/*  USO:
 * use Modules\Core\Support\Traits\ApiResponse;

class UsersController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->success(User::paginate(20));
    }
}
 *
 *
 *
 *
 *
 */
