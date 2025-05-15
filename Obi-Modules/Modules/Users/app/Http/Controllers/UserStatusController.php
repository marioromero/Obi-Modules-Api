<?php

namespace Modules\Users\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Users\Models\UserStatus;

use App\Http\Controllers\Controller;


class UserStatusController extends Controller
{


    public function index()
    {
        $data = UserStatus::paginate(15);
        return response()->json($data);
    }

    public function show(UserStatus $userStatus)
    {
        return response()->json($userStatus);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $userStatus = UserStatus::create($data);
        return response()->json($userStatus, 201);
    }

    public function update(Request $request, UserStatus $userStatus)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $userStatus->update($data);
        return response()->json($userStatus);
    }

    public function patch(Request $request, UserStatus $userStatus)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $userStatus->update($data);
        return response()->json($userStatus);
    }

    public function destroy(UserStatus $userStatus)
    {
        $userStatus->delete();
        return response()->noContent();
    }
}
