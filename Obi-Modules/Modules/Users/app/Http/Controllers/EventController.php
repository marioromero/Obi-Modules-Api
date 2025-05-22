<?php

namespace Modules\Users\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Users\Models\Event;

use App\Http\Controllers\Controller;


class EventController extends BaseApiController
{

    public function index()
    {
        $paginator = Event::paginate(15);
        return $this->paginated($paginator, 'Listado de events');
    }

    public function show(Event $event)
    {
        return $this->success($event, 'Event obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $event = Event::create($data);

        return $this->success($event, 'Event creado correctamente', 201);
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate(['name' => 'required|string']);
        $event->update($data);

        return $this->success($event, 'Event actualizado correctamente');
    }

    public function patch(Request $request, Event $event)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $event->update($data);

        return $this->success($event, 'Event parcialmente actualizado');
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return $this->success(null, 'Event eliminado correctamente', 204);
    }
}

