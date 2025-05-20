<?php

namespace Modules\Users\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Users\Models\User;

use App\Http\Controllers\Controller;


class UserController extends Controller
{


    public function index()
    {
        $data = User::paginate(15);
        return response()->json($data);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $user = User::create($data);
        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $user->update($data);
        return response()->json($user);
    }

    public function patch(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $user->update($data);
        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }
}
