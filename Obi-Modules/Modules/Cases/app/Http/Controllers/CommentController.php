<?php

namespace Modules\Cases\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Cases\Models\Comment;
use App\Http\Controllers\Controller;


class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    /**
     * Store a newly created resource in storage.
     */


    /**
     * Show the specified resource.
     */


    /**
     * Update the specified resource in storage.
     */


    /**
     * Remove the specified resource from storage.
     */


    public function index()
    {
        $data = Comment::paginate(15);
        return response()->json($data);
    }

    public function show(Comment $comment)
    {
        return response()->json($comment);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $comment = Comment::create($data);
        return response()->json($comment, 201);
    }

    public function update(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $comment->update($data);
        return response()->json($comment);
    }

    public function patch(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $comment->update($data);
        return response()->json($comment);
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response()->noContent();
    }
}
