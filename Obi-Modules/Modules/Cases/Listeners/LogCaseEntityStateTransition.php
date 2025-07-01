<?php

namespace Modules\Cases\Listeners;

use Spatie\ModelStates\Events\StateChanged;
use Modules\Cases\Models\CaseEntity;
use Modules\Cases\Models\CaseEntityStepLog;

class LogCaseEntityStateTransition
{
    private static $subStates = array (
  'Ingreso' => 
  array (
    'column' => 'signature_status',
    'values' => 
    array (
      0 => 'generado',
      1 => 'enviado a acepta',
      2 => 'notificado',
      3 => 'contrato pendiente',
      4 => 'mandato pendiente',
      5 => 'firmados',
    ),
    'default' => 'generado',
    'final' => 'firmados',
  ),
  'Denuncio' => 
  array (
    'column' => 'denounce_status',
    'values' => 
    array (
      0 => 'pendiente',
      1 => 'en proceso',
      2 => 'realizado',
    ),
    'default' => 'pendiente',
    'final' => 'realizado',
  ),
  'Programacion' => 
  array (
    'column' => 'scheduling_status',
    'values' => 
    array (
      0 => 'pendiente',
      1 => 'en proceso',
      2 => 'realizado',
    ),
    'default' => 'pendiente',
    'final' => 'realizado',
  ),
  'Visita' => 
  array (
    'column' => 'visit_status',
    'values' => 
    array (
      0 => 'pendiente',
      1 => 'en proceso',
      2 => 'realizado',
    ),
    'default' => 'pendiente',
    'final' => 'realizado',
  ),
  'Presupuesto' => 
  array (
    'column' => 'budget_status',
    'values' => 
    array (
      0 => 'pendiente',
      1 => 'en proceso',
      2 => 'realizado',
    ),
    'default' => 'pendiente',
    'final' => 'realizado',
  ),
  'Liquidacion' => 
  array (
    'column' => 'decision_status',
    'values' => 
    array (
      0 => 'en espera',
      1 => 'aprobado',
      2 => 'bajo deducible',
      3 => 'rechazado aseguradora',
      4 => 'rechazado liquidadora',
      5 => 'impugnado',
    ),
    'default' => 'en espera',
    'final' => 'impugnado',
  ),
  'Recaudacion' => 
  array (
    'column' => 'payment_status',
    'values' => 
    array (
      0 => 'pendiente',
      1 => 'cobranza',
      2 => 'parcialmente pagado',
      3 => 'pagado',
      4 => 'cobranza online',
    ),
    'default' => 'pendiente',
    'final' => 'pagado',
  ),
);

    public function handle(StateChanged $event): void
    {
        if (! $event->model instanceof CaseEntity) {
            return;
        }

        // 1) Preparar entidad y recargar datos
        $entity    = $event->model;
        $entity->refresh();

        // 2) Estados globales
        $fromState = $event->initialState
            ? class_basename($event->initialState)
            : null;
        $toState   = class_basename($event->finalState);

        // 3) Cargar configuración
        $cfg           = config('modules.Cases.CaseEntity_states');
        $statesList    = $cfg['states'];
        $closingSteps  = $cfg['closing_steps'];
        $overallCol    = $cfg['overall_status']['column'];

        // 4) Índices avance/retroceso
        $fromIdx = $fromState
            ? array_search($fromState, $statesList, true)
            : false;
        $toIdx   = array_search($toState,   $statesList, true);

        // 5) Capturar y actualizar sub-estado anterior
        $fromSubLogged = null;
        if ($fromState && isset(self::$subStates[$fromState])) {
            $oldCol = self::$subStates[$fromState]['column'];

            if ($toIdx > $fromIdx && ! in_array($toState, $closingSteps, true)) {
                // → Avance normal (no terminal): forzar 'final'
                $fromSubLogged      = self::$subStates[$fromState]['final'];
                $entity->{$oldCol} = $fromSubLogged;
            } elseif ($toIdx > $fromIdx && in_array($toState, $closingSteps, true)) {
                // → Avance a terminal: capturar real, no modificar
                $fromSubLogged = $entity->{$oldCol};
            } else {
                // ← Retroceso: capturar real y anular
                $fromSubLogged      = $entity->{$oldCol};
                $entity->{$oldCol} = null;
            }

            $entity->saveQuietly();
        }

        // 6) Abrir sub-estado del nuevo estado
        $toSub = null;
        if (isset(self::$subStates[$toState])) {
            $newCol     = self::$subStates[$toState]['column'];
            $newDefault = self::$subStates[$toState]['default'];
            $entity->{$newCol} = $newDefault;
            $entity->saveQuietly();
            $toSub = $newDefault;
        }

        // 7) Cierre global
        if (in_array($toState, $closingSteps, true)) {
            $entity->{$overallCol} = 'cerrado';
            $entity->saveQuietly();
        }

        // 8) Des-cierre
        if (in_array($fromState, $closingSteps, true)
            && ! in_array($toState, $closingSteps, true)
        ) {
            $lastLog = CaseEntityStepLog::query()
                ->where('case_id', $entity->id)
                ->whereNotIn('to_state', $closingSteps)
                ->where('type', 'state')
                ->orderBy('created_at','desc')
                ->first();

            if ($lastLog && isset(self::$subStates[$lastLog->to_state])) {
                $col = self::$subStates[$lastLog->to_state]['column'];
                $entity->{$col} = self::$subStates[$lastLog->to_state]['default'];
            }

            $entity->{$overallCol} = 'con pendientes';
            $entity->saveQuietly();
        }

        // 9) Transiciones normales: verificar si el nuevo subestado debe marcar como pendiente
        if (!in_array($toState, $closingSteps, true)
            && !in_array($fromState, $closingSteps, true)) {

            $pendingSubStates = $cfg['overall_status']['triggers']['pending']['sub_states'] ?? [];

            if ($toSub && in_array($toSub, $pendingSubStates, true)) {
                // Si el nuevo subestado está en la lista de pendientes, usar "con_pendientes"
                $entity->{$overallCol} = 'con pendientes'; // segundo valor del array overall_status.values
            } else {
                // Si no, usar el valor por defecto
                $entity->{$overallCol} = $cfg['overall_status']['default'];
            }
            $entity->saveQuietly();
        }

        // 10) Registrar en la bitácora
        CaseEntityStepLog::create([
            'case_id' => $entity->id,
            'from_state'  => $fromState,
            'to_state'    => $toState,
            'from_sub'    => $fromSubLogged,
            'to_sub'      => $toSub,
            'type'        => 'state',
            'user_id'     => auth()->id(),
            'payload'     => json_encode($entity->getChanges()),
            'comments'    => null,
        ]);
    }
}