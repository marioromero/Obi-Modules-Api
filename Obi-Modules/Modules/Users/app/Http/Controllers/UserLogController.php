<?php

namespace Modules\Users\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Users\Models\UserLog;

use App\Http\Controllers\Controller;


class UserLogController extends Controller
{


    public function index()
    {
        $data = UserLog::paginate(15);
        return response()->json($data);
    }

    public function show(UserLog $userLog)
    {
        return response()->json($userLog);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $userLog = UserLog::create($data);
        return response()->json($userLog, 201);
    }

    public function update(Request $request, UserLog $userLog)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $userLog->update($data);
        return response()->json($userLog);
    }

    public function patch(Request $request, UserLog $userLog)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $userLog->update($data);
        return response()->json($userLog);
    }

    public function destroy(UserLog $userLog)
    {
        $userLog->delete();
        return response()->noContent();
    }
}
