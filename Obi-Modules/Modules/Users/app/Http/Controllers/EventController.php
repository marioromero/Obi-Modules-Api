<?php

namespace Modules\Users\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Users\Models\Event;

use App\Http\Controllers\Controller;


class EventController extends Controller
{


    public function index()
    {
        $data = Event::paginate(15);
        return response()->json($data);
    }

    public function show(Event $event)
    {
        return response()->json($event);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $event = Event::create($data);
        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $event->update($data);
        return response()->json($event);
    }

    public function patch(Request $request, Event $event)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $event->update($data);
        return response()->json($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->noContent();
    }
}
