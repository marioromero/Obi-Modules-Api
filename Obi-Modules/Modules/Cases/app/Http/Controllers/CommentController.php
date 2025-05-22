<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Cases\Models\Comment;
use App\Http\Controllers\Controller;


class CommentController extends BaseApiController
{
   
    public function index()
    {
        $paginator = Comment::paginate(15);
        return $this->paginated($paginator, 'Listado de comments');
    }

    public function show(Comment $comment)
    {
        return $this->success($comment, 'Comment obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $comment = Comment::create($data);

        return $this->success($comment, 'Comment creado correctamente', 201);
    }

    public function update(Request $request, Comment $comment)
    {
        $data = $request->validate(['name' => 'required|string']);
        $comment->update($data);

        return $this->success($comment, 'Comment actualizado correctamente');
    }

    public function patch(Request $request, Comment $comment)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $comment->update($data);

        return $this->success($comment, 'Comment parcialmente actualizado');
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        return $this->success(null, 'Comment eliminado correctamente', 204);
    }
}

