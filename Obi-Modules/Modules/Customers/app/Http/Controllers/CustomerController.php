<?php

namespace Modules\Customers\app\Http\Controllers;

use Modules\Core\app\Http\BaseApiController;
use Modules\Cases\Models\CaseEntity;
use Modules\Customers\app\Http\Requests\StoreCustomerRequest;
use Modules\Customers\app\Http\Requests\UpdateCustomerRequest;
use Modules\Customers\Models\Customer;
use Modules\Customers\Models\CustomerDetail;
use Modules\Users\Models\TraroUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\app\Helpers\ColumnMap;
use Modules\Core\app\Helpers\RutValidator;
use Illuminate\Validation\ValidationException;


class CustomerController extends BaseApiController
{
    public function index()
    {
        $customers = Customer::all();
        return $this->success($customers, 'Listado de clientes', 200);
    }

    public function show(Customer $customer)
    {
        $detail = CustomerDetail::find($customer->id);
        if (! $detail) {
            return $this->error('Cliente no existe', 404);
        }
        return $this->success($detail, 'Cliente obtenido correctamente', 200);
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());
        return $this->success($customer, 'Cliente creado correctamente', 201);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        try {
            $customer->fill($request->validated())->save();
            return $this->success($customer->refresh(), 'Cliente actualizado correctamente', 200);
        } catch (ValidationException $e) {
            return $this->error('Errores de validación', 422, $e->errors());
        }
    }

    public function patch(UpdateCustomerRequest $request, Customer $customer)
    {
        try {
            $customer->fill($request->validated())->save();
            return $this->success($customer->refresh(), 'Cliente parcialmente actualizado', 200);
        } catch (ValidationException $e) {
            return $this->error('Errores de validación', 422, $e->errors());
        }
    }

    public function destroy(Customer $customer)
    {
        // Regla de negocio: no eliminar si tiene casos asignados
        $hasCases = CaseEntity::on('cases_db')
            ->where('customer_id', $customer->id)
            ->exists();

        if ($hasCases) {
            return $this->error('No se puede eliminar el cliente: tiene casos asignados.', 409);
        }

        $customer->delete();
        return $this->success(null, 'Cliente eliminado exitosamente', 200);
    }

    public function softDelete(Customer $customer)
    {
        DB::connection('customers_db')
            ->table($customer->getTable())
            ->where('id', $customer->id)
            ->update([
                'softdeleted' => DB::raw('1 - softdeleted'),
                'updated_at'  => now(),
            ]);

        return $this->success($customer->refresh(), 'Cliente actualizado (softdeleted toggled).', 200);
    }

     // DNI exacto → devuelve objeto completo
    public function showCustomerByDni(string $dni)
    {
        try {
            // 1. Limpieza básica
            $dni = str_replace(['.', ' '], '', trim($dni));
            if ($dni === '') {
                return $this->error('DNI inválido', 422);
            }

            // 2. Separar número y DV
            if (str_contains($dni, '-')) {
                [$num, $dv] = explode('-', $dni, 2);
            } else {
                $num = substr($dni, 0, -1);
                $dv  = substr($dni, -1);
            }

            // 3. Normalización
            $num = preg_replace('/\D+/', '', $num ?? '');
            $dv  = strtolower($dv ?? '');

            if ($num === '' || $dv === '') {
                return $this->error('DNI inválido', 422);
            }

            // 4. RUT normalizado
            $normalized = $num . '-' . $dv;

            // 5. Validación REAL del RUT (DV por cálculo)
            if (! RutValidator::isValidRut($normalized)) {
                return $this->error('El RUT no es válido.', 422);
            }

            // 6. Búsqueda en BD
            $customer = Customer::query()
                ->with('assignedAgent:id,name')
                ->where('dni', $normalized)
                ->first();

            if (! $customer) {
                return $this->success(null, 'No existe', 204);
            }

            // 7. Respuesta
            $data = $customer->toArray();
            $data['assigned_agent'] = $customer->assigned_agent ?? null;
            $data['agent_name']     = $customer->assignedAgent->name ?? null;

            return $this->success($data, 'Cliente encontrado', 200);
        } catch (\Throwable $e) {
            // Registrar el error para depuración (en un entorno real, usarías el logger)
            return $this->error('Error interno al buscar el cliente: ' . $e->getMessage(), 500);
        }
    }

    // Verifica existencia por DNI responde 1 o 0
    public function verifyExistingCustomer(string $dni)
    {
        $dni = str_replace(['.', ' '], '', trim($dni));
        if ($dni !== '' && str_contains($dni, '-')) {
            [$num, $dv] = explode('-', $dni, 2);
            $dni = preg_replace('/\D/', '', $num) . '-' . strtoupper($dv);
        }

        $customer = $dni === '' ? null : Customer::query()->where('dni', $dni)->first();

        if ($customer) {
            $fullName = trim(($customer->name ?? '') . ' ' . ($customer->lastname ?? ''));
            return $this->success(1, "El RUT del cliente ya se encuentra registrado a nombre de: {$fullName}.", 200);
        }

        return $this->success(0, '', 200);
    }

    // Devuelve sólo tags/comments por DNI del usuario
    public function getTagsByDniUser(string $dni)
    {
        $dni = preg_replace('/[.\s]/', '', (string) $dni);

        $customer = Customer::query()
            ->select(['tags', 'comments'])
            ->where('dni', $dni)
            ->first();

        if (! $customer) {
            return $this->success(null, 'No existe', 204);
        }

        $maybeJson = function ($value) {
            if (!is_string($value)) return $value;
            $t = trim($value);
            if ($t === '' || strtolower($t) === 'null') return null;
            $first = $t[0] ?? '';
            if ($first !== '{' && $first !== '[') return $value;
            $decoded = json_decode($t, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        };

        return $this->success(
            [
                'tags'     => $maybeJson($customer->tags),
                'comments' => $maybeJson($customer->comments),
            ],
            'Tags y comentarios obtenidos',
            200
        );
    }
    public function customersByDni(string $dni)
    {
        $term = str_replace(['.', ' '], '', trim($dni));

        $customers = Customer::query()
            ->where('dni', 'LIKE', "%{$term}%")
            ->limit(15)
            ->get();

        $results = $customers->map(function ($c) {
            return [
                'id'            => $c->id,
                'text'          => "{$c->dni} – {$c->name} {$c->lastname}",
                'full_customer' => $c, // objeto completo del cliente
            ];
        });

        return $this->success($results, 'Clientes encontrados por DNI', 200);
    }

    public function customersByName(string $q)
    {
        // Validación mínima inline
        $q = trim($q);
        if ($q === '' || mb_strlen($q) > 100) {
            return $this->error('Parámetro de búsqueda inválido', 422);
        }

        $term = mb_strtolower($q, 'UTF-8');

        $results = Customer::query()
            ->select(['id'])
            ->selectRaw("CONCAT(name, ' ', lastname) AS text")
            ->whereRaw("LOWER(CONCAT(name, ' ', lastname)) LIKE ?", ["%{$term}%"])
            ->orderBy('name')
            ->limit(15)
            ->get();

        return $this->success($results, 'Clientes encontrados', 200);
    }
    public function getCustomersByAgent(TraroUser $agent)
    {
        $customers = CustomerDetail::query()
            ->where('assigned_agent', (int) $agent->id)
            ->orderByDesc('id')
            ->get();

        if ($customers->isEmpty()) {
            return $this->success([], "No hay clientes asignados al ejecutivo/a '{$agent->name}'.", 200);
        }

        return $this->success($customers, "Clientes asignados al ejecutivo/a '{$agent->name}'.", 200);
    }

    public function updateStatusForCustomer(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'is_enabled' => ['required', 'boolean'],
        ]);

        $customer->is_enabled = $data['is_enabled'];
        $customer->save();

        return $this->success(
            $customer->refresh(),
            'Estado del cliente actualizado correctamente'
        );
    }

    //Reasignacion masiva de clientes a un nuevo agente
    public function customerReassignment(Request $request, int $agent)
    {
        //El body debe ser un array de IDs, que sean numéricos y evitar duplicados
        $ids = $request->all();

        if (!is_array($ids) || empty($ids)) {
            return $this->error('Debe enviar un array de IDs de clientes.', 422);
        }

        $normalizedIds = [];

        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                return $this->error('Todos los IDs deben ser numéricos.', 422);
            }

            $normalizedIds[] = (int) $id;
        }

        $normalizedIds = array_values(array_unique($normalizedIds));

        //Validar que el agente exista
        $userConnection = (new \Modules\Users\Models\TraroUser)->getConnectionName() ?: 'traro_db';

        $agentExists = DB::connection($userConnection)
            ->table('users')
            ->where('id', $agent)
            ->exists();

        if (! $agentExists) {
            return $this->error("El agente con ID {$agent} no existe.", 404);
        }

        //Reasignar clientes al nuevo agente
        $affected = Customer::whereIn('id', $normalizedIds)->update([
            'assigned_agent' => $agent,
        ]);

        return $this->success([], 'Clientes reasignados correctamente');
    }
}
