<?php

namespace Modules\Customers\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;
use Modules\Customers\Models\Customer;
use Modules\Customers\Models\Tag;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TagController extends BaseApiController
{

    public function index()
    {
        $tags = Tag::all();
        return $this->success($tags, 'Listado de tags');
    }

    public function show(Tag $tag)
    {
        return $this->success($tag, 'Tag obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $tag = Tag::create($data);

        return $this->success($tag, 'Tag creado correctamente', 201);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate(['name' => 'required|string']);
        $tag->update($data);

        return $this->success($tag, 'Tag actualizado correctamente');
    }

    public function patch(Request $request, Tag $tag)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $tag->update($data);

        return $this->success($tag, 'Tag parcialmente actualizado');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return $this->success(null, 'Tag eliminado correctamente', 204);
    }

    // Estado de tags por cliente
    public function customerTags(Customer $customer)
    {
        return $this->success($customer->tags ?? [], 'Listado de tags del cliente');
    }

    public function toggleByName(Request $request, Customer $customer, string $name)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $tags  = collect($customer->tags ?? []);
        $index = $tags->search(fn ($t) => $t['name'] === $name);

        if ($index === false) {
            // Si el tag aún no está en el JSON, lo agrega obteniendo su color real
            $color = Tag::where('name', $name)->value('color') ?? '#000000';

            $tags->push([
                'name'    => $name,
                'color'   => $color,
                'enabled' => $data['enabled'],
            ]);
        } else {
            // Ya existe → solo cambia enabled
            $tags[$index]['enabled'] = $data['enabled'];
        }

        $customer->tags = $tags->values()->toArray();
        $customer->save();

        return $this->success($customer->tags, 'Tag actualizado correctamente');
    }

}
