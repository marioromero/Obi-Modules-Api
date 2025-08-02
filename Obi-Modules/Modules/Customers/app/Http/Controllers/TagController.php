<?php

namespace Modules\Customers\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;
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
    public function customerTags(int $customerId)       
    {
        $customer = Customer::findOrFail($customerId);

        return $this->success(
            collect($customer->tags ?? [])->values(),
            'Listado de tags del cliente'
        );
    }

    public function toggleByName(Request $request, Customer $customer, string $name)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        // 1) Obtiene el JSON como colección
        $tagsCol = collect($customer->tags ?? []);

        // 2) Lo pasa a array para poder modificar directamente
        $tags = $tagsCol->toArray();

        // 3) Busca el índice por nombre
        $index = array_search($name, array_column($tags, 'name'), true);

        if ($index === false) {
            // Tag no existe en el JSON → lo agrega con su color real
            $color = Tag::where('name', $name)->value('color') ?? '#000000';

            $tags[] = [
                'name'    => $name,
                'color'   => $color,
                'enabled' => $data['enabled'],
            ];
        } else {
            // Tag existe → actualiza enabled
            $tags[$index]['enabled'] = $data['enabled'];
        }

        // 4) Guarda el array (Eloquent lo serializa a JSON)
        $customer->tags = array_values($tags);
        $customer->save();

        return $this->success($customer->tags, 'Tag actualizado correctamente');
    }


}
