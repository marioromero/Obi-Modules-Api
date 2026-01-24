<?php

namespace Modules\Mailing\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Mailing\Models\EmailSchedule;
use Illuminate\Support\Facades\DB;
use Modules\Core\app\Helpers\ColumnMap;
use Illuminate\Support\Facades\Auth;
use Modules\Mailing\app\Helpers\EmailScheduleHelper;

class EmailScheduleController extends BaseApiController
{

    public function index()
    {
        $keys = [
            'schedule_id','customer_set_id','customer_set_name',
            'start_in','ending_at','status_sends','is_retry',
            'created_by_user_id','created_by_user_name','status',
        ];

        $rows = DB::connection('mailing_db')
            ->table('v_email_schedules_list')
            ->select($keys)
            ->orderBy('start_in', 'asc')
            ->get();

        return $this->success([
            'columns' => ColumnMap::translate($keys, 'mailing'),
            'rows'    => $rows,
        ], 'Listado de programaciones');
    }

    public function show(EmailSchedule $emailSchedule)
    {
        return $this->success($emailSchedule, 'EmailSchedule obtenido correctamente');
    }

    public function previewSchedule(Request $request)
    {
        $data = $request->validate([
            'customer_set_id'    => 'required|integer',
            'email_template_id'  => 'required|integer',
            'start_in'           => 'required', // permite YYYY-MM-DD o YYYY-MM-DD HH:MM:SS
            'only_business_days' => 'required|boolean',
        ]);

        try {
            // 1) Set + count destinatarios
            $set = DB::connection('mailing_db')
                ->table('customers_sets')
                ->select(['id', 'name'])
                ->where('id', $data['customer_set_id'])
                ->first();

            if (! $set) {
                return $this->error('Set de clientes no existe.', 422);
            }

            $recipientsCount = (int) DB::connection('mailing_db')
                ->table('customer_detail')
                ->where('customer_set_id', $data['customer_set_id'])
                ->count();

            // 2) Plantilla + correo depto
            $tpl = DB::connection('mailing_db')
                ->table('email_templates as t')
                ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
                ->select([
                    't.id',
                    't.name as template_name',
                    'd.email as department_email',
                ])
                ->where('t.id', $data['email_template_id'])
                ->first();

            if (! $tpl) {
                return $this->error('Plantilla no existe.', 422);
            }

            // 3)  Resolver start/end con la MISMA lógica del store
            [$startAt, $endingAt, $timeText] = EmailScheduleHelper::resolveStartAndEnd(
                (string) $data['start_in'],
                $recipientsCount,
                (bool) $data['only_business_days']
            );

            return $this->success([
                'set_clientes' => [
                    'nombre'                 => $set->name,
                    'cantidad_destinatarios' => $recipientsCount,
                ],
                'plantilla' => [
                    'nombre'              => $tpl->template_name,
                    'correo_departamento' => $tpl->department_email,
                ],
                'fecha_inicio' => $startAt->format('Y-m-d H:i:s'),
                'tiempo_estimado_envio' => $timeText,
            ], 'Preview de programación');

        } catch (\Throwable $e) {
            // por si algo falla inesperado
            return $this->error('No se pudo generar el preview.', 500);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_set_id'    => 'required|integer',
            'email_template_id'  => 'required|integer',
            'start_in'           => 'required', // permite 'YYYY-MM-DD' o con hora
            'only_business_days' => 'required|boolean',
            'user_id'            => 'nullable|integer|min:1',
        ]);

        $userId = Auth::id() ?: ($data['user_id'] ?? null);
        if (! $userId) {
            return $this->error('Debe enviar user_id si no existe sesión (Auth).', 422);
        }

        $conn = DB::connection('mailing_db');

        $recipientsCount = (int) $conn
            ->table('customer_detail')
            ->where('customer_set_id', (int) $data['customer_set_id'])
            ->count();

        if ($recipientsCount <= 0) {
            return $this->error('El set no tiene destinatarios (customer_detail).', 422);
        }

        // start/end normalizados en TZ Chile (ya funciona, no tocar)
        [$startAt, $endingAt] = EmailScheduleHelper::resolveStartAndEnd(
            (string) $data['start_in'],
            $recipientsCount,
            (bool) $data['only_business_days']
        );

        try {
            $schedule = $conn->transaction(function () use ($data, $userId, $startAt, $endingAt, $conn) {

                // 1) Crear schedule
                $schedule = \Modules\Mailing\Models\EmailSchedule::create([
                    'start_in'           => $startAt->format('Y-m-d H:i:s'),
                    'sends_ok'           => 0,
                    'failed_or_pendings' => 0,
                    'is_retry'           => false,
                    'status'             => 'pending',
                    'only_business_days' => (bool) $data['only_business_days'],
                    'user_id'            => (int) $userId,
                    'customer_set_id'    => (int) $data['customer_set_id'],
                    'email_template_id'  => (int) $data['email_template_id'],
                ]);

                // 2) Precargar sends (uno por destinatario del set)
                // Nota: usamos chunk() (no chunkById) para evitar problemas con joins/aliases.
                $conn->table('customer_detail as cd')
                    ->join('v_customers_mailing as c', 'c.customer_id', '=', 'cd.customer_id')
                    ->where('cd.customer_set_id', (int) $data['customer_set_id'])
                    ->select([
                        'cd.id as customer_detail_id',
                        'c.name',
                        'c.lastname',
                        'c.email',
                    ])
                    ->orderBy('cd.id')
                    ->chunk(500, function ($rows) use ($conn, $schedule) {

                        $inserts = [];

                        foreach ($rows as $r) {
                            $fullName = trim(($r->name ?? '') . ' ' . ($r->lastname ?? ''));

                            $inserts[] = [
                                'email_schedule_id'  => (int) $schedule->id,
                                'customer_detail_id' => (int) $r->customer_detail_id,
                                'customer_name'      => $fullName !== '' ? $fullName : 'N/A',
                                'email'              => (string) ($r->email ?? ''),
                                'sent_at'            => null,       // se rellena SOLO cuando quede OK
                                'status'             => 'pending',  // (luego lo puedes dejar por default en BD)
                            ];
                        }

                        if (! empty($inserts)) {
                            $conn->table('sends')->insert($inserts);
                        }
                    });

                return $schedule;
            });

            return $this->success($schedule, 'Programación creada correctamente', 201);

        } catch (\Throwable $e) {
            return $this->error('No se pudo crear la programación: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, EmailSchedule $emailSchedule)
    {
        $data = $request->validate(['name' => 'required|string']);
        $emailSchedule->update($data);

        return $this->success($emailSchedule, 'EmailSchedule actualizado correctamente');
    }

    public function patch(Request $request, EmailSchedule $emailSchedule)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $emailSchedule->update($data);

        return $this->success($emailSchedule, 'EmailSchedule parcialmente actualizado');
    }

    public function destroy(EmailSchedule $emailSchedule)
    {
        $emailSchedule->delete();
        return $this->success(null, 'EmailSchedule eliminado correctamente', 204);
    }
}

