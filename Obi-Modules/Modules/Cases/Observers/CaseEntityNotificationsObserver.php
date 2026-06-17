<?php

namespace Modules\Cases\Observers;

use Illuminate\Support\Facades\DB;
use Modules\Cases\Models\CaseEntity;

class CaseEntityNotificationsObserver
{

    //Se ejecuta automáticamente luego de un update del modelo
    //Deshabilita las notificaciones en el último case_flow del id del caso si el nuevo estado no está permitido según el archivo de configuracion
    public function updated(CaseEntity $case): void
    {
        //Solo se ejecuta si cambió el estado
        if (! $case->wasChanged('state')) {
            return;
        }

        //Basename del estado actual
        $currentBase = class_basename($case->state::class);

        // 2) Leer configuración desde configurations_db
        try {
            $typeId = DB::connection('configurations_db')
                ->table('types')
                ->where('name', 'States_machine')
                ->value('id');

            if (! $typeId) {
                return;
            }

            $raw = DB::connection('configurations_db')
                ->table('configurations')
                ->where('type_id', $typeId)
                ->value('content');

            $cfg = json_decode($raw ?? '[]', true) ?: [];
            $allowed = (array) ($cfg['steps_with_whatsapp_notifications'] ?? []);
        } catch (\Throwable $e) {
            return;
        }

        //Si el estado no está permitido, apaga active_notifications en el último case_flow = 0
        if (! in_array($currentBase, $allowed, true)) {
            $lastId = DB::connection('traro_db')
                ->table('case_flows')
                ->where('obi_case_id', $case->id)
                ->where('is_active', 1)
                ->max('id');

            if ($lastId) {
                DB::connection('traro_db')
                    ->table('case_flows')
                    ->where('id', $lastId)
                    ->where('active_notifications', '!=', 0)
                    ->update(['active_notifications' => 0]);
            }
        }
    }
}
