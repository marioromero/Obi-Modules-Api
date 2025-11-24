<?php

namespace Modules\Core\app\Http\Controllers;

use Modules\Core\app\Http\BaseApiController;
use Modules\Core\Models\Softdelete;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoftdeleteController extends BaseApiController
{

    public function index()
    {
        $rows = DB::connection('configurations_db')
            ->table('v_softdelete_entities')
            ->orderBy('deleted_at', 'desc')
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'entity_type' => $row->entity_type,
                'entity_id'   => $row->entity_id,    
                'deleted_at'  => $row->deleted_at,
                'user'        => $row->deleted_by_user_name,
                'detail'      => $this->buildDetail($row),
            ];
        });

        return $this->success($data, 'Listado de entidades softdeleted');
    }

    public function show(Softdelete $softdelete)
    {
        return $this->success($softdelete, 'Softdelete obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $softdelete = Softdelete::create($data);

        return $this->success($softdelete, 'Softdelete creado correctamente', 201);
    }

    public function update(Request $request, Softdelete $softdelete)
    {
        $data = $request->validate(['name' => 'required|string']);
        $softdelete->update($data);

        return $this->success($softdelete, 'Softdelete actualizado correctamente');
    }

    public function patch(Request $request, Softdelete $softdelete)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $softdelete->update($data);

        return $this->success($softdelete, 'Softdelete parcialmente actualizado');
    }

    public function destroy(Softdelete $softdelete)
    {
        $softdelete->delete();
        return $this->success(null, 'Softdelete eliminado correctamente', 204);
    }

    // Helper para enviar legibles los details al front
    protected function buildDetail(object $row): string
    {
        try {
            switch ($row->entity_type) {

                case 'case':
                    $case = DB::connection('cases_db')
                        ->table('cases')
                        ->where('id', $row->entity_id)
                        ->first();

                    if (!$case) return "Caso ID {$row->entity_id}";

                    $code = $case->code ?? "ID {$case->id}";

                    // buscar nombre del cliente
                    $customerName = null;
                    if (!empty($case->customer_id)) {
                        $customer = DB::connection('customers_db')
                            ->table('customers')
                            ->where('id', $case->customer_id)
                            ->first();

                        if ($customer) {
                            $cn = trim(($customer->name ?? '') . ' ' . ($customer->last_name ?? ''));
                            $customerName = $cn !== '' ? $cn : null;
                        }
                    }

                    if ($customerName) {
                        return "Caso {$code} - Cliente: {$customerName}";
                    }

                    return "Caso {$code}";

                case 'accident_type':
                    $at = DB::connection('cases_db')
                        ->table('accident_types')
                        ->where('id', $row->entity_id)
                        ->first();
                    return $at ? "Tipo de siniestro: {$at->name}" : "Tipo de siniestro #{$row->entity_id}";

                case 'customer':
                    $customer = DB::connection('customers_db')
                        ->table('customers')
                        ->where('id', $row->entity_id)
                        ->first();

                    if (!$customer) return "Cliente ID {$row->entity_id}";

                    $name = trim(($customer->name ?? '') . ' ' . ($customer->last_name ?? ''));
                    $name = $name !== '' ? $name : "ID {$customer->id}";
                    $rut = $customer->rut ?? $customer->dni ?? null;

                    return $rut
                        ? "Cliente: {$name} - RUT {$rut}"
                        : "Cliente: {$name}";

                case 'bank':
                    $bank = DB::connection('banks_db')
                        ->table('banks')
                        ->where('id', $row->entity_id)
                        ->first();
                    return $bank ? "Banco: {$bank->name}" : "Banco ID {$row->entity_id}";

                case 'insurer':
                    $insurer = DB::connection('banks_db')
                        ->table('insurers')
                        ->where('id', $row->entity_id)
                        ->first();
                    return $insurer ? "Aseguradora: {$insurer->name}" : "Aseguradora ID {$row->entity_id}";

                case 'loss_adjuster':
                    $la = DB::connection('banks_db')
                        ->table('loss_adjusters')
                        ->where('id', $row->entity_id)
                        ->first();
                    return $la ? "Liquidadora: {$la->name}" : "Liquidadora ID {$row->entity_id}";

                case 'user':
                    $user = DB::connection('traro_db')
                        ->table('users')
                        ->where('id', $row->entity_id)
                        ->first();

                    if (!$user) return "Usuario ID {$row->entity_id}";

                    $name = $user->name ?? "ID {$user->id}";
                    return $user->email
                        ? "Usuario: {$name} ({$user->email})"
                        : "Usuario: {$name}";
            }

        } catch (\Throwable $e) {
            // Log si quieres
            // \Log::warning('Softdelete detail error', ['error' => $e->getMessage()]);
        }

        return "{$row->entity_type} #{$row->entity_id}";
    }
}
