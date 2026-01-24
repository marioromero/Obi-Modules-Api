<?php

namespace Modules\Mailing\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Mailing\Models\EmailSchedule;
use Modules\Mailing\Models\Send;
use Modules\Mailing\app\Helpers\ChileBusinessDays;
use Modules\Mailing\app\Helpers\MailingRunHelper;

class RunMailingSchedulesCommand extends Command
{
    protected $signature = 'mailing:run';
    protected $description = 'Procesa campañas de mailing con throttling por envío';

    private const WINDOW_START = '08:00:00';
    private const WINDOW_END   = '23:59:59';

    // Para pruebas lo dejaste con debug. Si quieres, lo apagas.
    private const DEBUG = true;

    public function handle(): int
    {
        $now = MailingRunHelper::nowChile();

        // 1) Solo en ventana
        if (!MailingRunHelper::isWithinWindow($now, self::WINDOW_START, self::WINDOW_END)) {
            $this->line("[SKIP] Fuera de horario ({$now->format('H:i:s')}). Ventana: 08:00-23:59");
            return self::SUCCESS;
        }

        // 2) Elegir campaña
        $schedule = $this->pickScheduleToProcess($now);

        if (!$schedule) {
            $this->line('[OK] No hay campañas para procesar.');
            return self::SUCCESS;
        }

        $this->info(
            "[SCHEDULE] #{$schedule->id} status={$schedule->status} is_retry=" .
            ($schedule->is_retry ? '1' : '0')
        );

        // 3) only_business_days => si hoy no es hábil, no envía, pero queda in_progress
        if ($schedule->only_business_days && !ChileBusinessDays::isBusinessDay($now)) {
            $this->warn("[WAIT] Hoy no es día hábil. Schedule #{$schedule->id} se mantiene '{$schedule->status}' y no se envía.");
            return self::SUCCESS;
        }

        // Intervalo real (send_once)
        $interval = MailingRunHelper::intervalSeconds((int) ($schedule->send_once ?? 25), 25);

        // Cap por día (según ventana e intervalo)
        $maxPerDay = MailingRunHelper::maxPerDay(self::WINDOW_START, self::WINDOW_END, $interval);

        // 4) Máximo hoy por tiempo restante
        $maxToday = MailingRunHelper::maxSendsForRemainingWindow(
            $now,
            self::WINDOW_END,
            $interval,
            $maxPerDay,
            self::DEBUG,
            fn(string $msg) => $this->line($msg)
        );

        if ($maxToday <= 0) {
            $this->line('[SKIP] No hay tiempo restante en la ventana para enviar.');
            return self::SUCCESS;
        }

        // 5) Tomar pending (hasta maxToday)
        $pendingSends = Send::on('mailing_db')
            ->where('email_schedule_id', $schedule->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($maxToday)
            ->get();

        if ($pendingSends->isEmpty()) {
            $this->finishScheduleAndMaybeCreateRetry($schedule);
            return self::SUCCESS;
        }

        $this->info("[BATCH] Procesando {$pendingSends->count()} sends (máximo hoy: {$maxToday}) interval={$interval}s");

        // 6) Precargar plantilla/departamento
        $template = DB::connection('mailing_db')
            ->table('email_templates as t')
            ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
            ->select([
                't.id',
                't.name as template_name',
                't.content as template_content',
                'd.email as from_email',
                'd.name as from_name',
            ])
            ->where('t.id', $schedule->email_template_id)
            ->first();

        if (!$template) {
            $this->error("[ERROR] No existe email_template_id={$schedule->email_template_id} para schedule #{$schedule->id}");
            return self::FAILURE;
        }

        // 7) Loop envíos
        foreach ($pendingSends as $idx => $send) {

            // corte por ventana
            $nowLoop = MailingRunHelper::nowChile();
            if (!MailingRunHelper::isWithinWindow($nowLoop, self::WINDOW_START, self::WINDOW_END)) {
                $this->warn('[STOP] Llegó fin de ventana. Corte limpio.');
                break;
            }

            $payload = [
                'schedule_id' => (int) $schedule->id,
                'send_id'     => (int) $send->id,
                'is_retry'    => (bool) $schedule->is_retry,

                'to_email'    => (string) $send->email,
                'to_name'     => (string) $send->customer_name,

                'from_email'  => (string) ($template->from_email ?? ''),
                'from_name'   => (string) ($template->from_name ?? ''),
                'subject'     => (string) ($template->template_name ?? 'Sin asunto'),
                'html_body'   => (string) ($template->template_content ?? ''),
            ];

            // ÚNICO lugar que cambiarás cuando Mario entregue el método real
            $result = $this->sendOrLog($payload);

            if ($result['success']) {
                $send->status  = 'ok';
                $send->sent_at = MailingRunHelper::nowChile()->format('Y-m-d H:i:s');
                $send->save();

                $schedule->sends_ok = (int) $schedule->sends_ok + 1;
                $schedule->save();
            } else {
                $send->status  = 'failed';
                $send->sent_at = null;
                $send->save();

                $schedule->failed_or_pendings = (int) $schedule->failed_or_pendings + 1;
                $schedule->save();

                $this->warn("[FAILED] send_id={$send->id} reason=" . ($result['message'] ?? 'unknown'));
            }

            // Throttle real según send_once
            if ($idx < ($pendingSends->count() - 1)) {
                sleep($interval);
            }
        }

        // 8) Finalizar y crear retry si corresponde
        $this->finishScheduleAndMaybeCreateRetry($schedule);

        return self::SUCCESS;
    }

    private function pickScheduleToProcess(Carbon $now): ?EmailSchedule
    {
        $inProgress = EmailSchedule::on('mailing_db')
            ->where('status', 'in_progress')
            ->orderBy('start_in')
            ->first();

        if ($inProgress) {
            return $inProgress;
        }

        $pending = EmailSchedule::on('mailing_db')
            ->where('status', 'pending')
            ->where('start_in', '<=', $now->format('Y-m-d H:i:s'))
            ->orderBy('start_in')
            ->first();

        if (!$pending) {
            return null;
        }

        $pending->status = 'in_progress';
        $pending->save();

        return $pending;
    }

    private function finishScheduleAndMaybeCreateRetry(EmailSchedule $schedule): void
    {
        $pendingLeft = Send::on('mailing_db')
            ->where('email_schedule_id', $schedule->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingLeft) {
            return;
        }

        if ($schedule->status !== 'finished') {
            $schedule->ending_at = Carbon::now('America/Santiago')->format('Y-m-d H:i:s');
            $schedule->status    = 'finished';
            $schedule->save();

            $this->info("[FINISH] Schedule #{$schedule->id} => finished at {$schedule->ending_at}");
        }

        // retry no genera retry
        if ($schedule->is_retry) {
            return;
        }

        $residual = Send::on('mailing_db')
            ->where('email_schedule_id', $schedule->id)
            ->whereIn('status', ['failed', 'pending'])
            ->orderBy('id')
            ->get(['customer_detail_id', 'customer_name', 'email']);

        if ($residual->isEmpty()) {
            $this->info("[RETRY] No hay resagados. No se crea retry.");
            return;
        }

        // start_in: mañana 08:00 (o próximo hábil 08:00 si only_business_days=true)
        $retryStart = MailingRunHelper::nextRetryStartAt(
            (bool) $schedule->only_business_days,
            self::WINDOW_START,
            fn(Carbon $dt) => ChileBusinessDays::isBusinessDay($dt)
        );

        $retry = EmailSchedule::on('mailing_db')->create([
            'start_in'           => $retryStart->format('Y-m-d H:i:s'),
            'send_once'          => (int) ($schedule->send_once ?? 25),
            'sends_ok'           => 0,
            'failed_or_pendings' => 0,
            'is_retry'           => true,
            'status'             => 'pending',
            'only_business_days' => (bool) $schedule->only_business_days,
            'user_id'            => (int) $schedule->user_id,
            'customer_set_id'    => (int) $schedule->customer_set_id,
            'email_template_id'  => (int) $schedule->email_template_id,
        ]);

        $inserts = [];
        foreach ($residual as $r) {
            $inserts[] = [
                'email_schedule_id' => (int) $retry->id,
                'customer_detail_id'=> (int) $r->customer_detail_id,
                'customer_name'     => (string) $r->customer_name,
                'email'             => (string) $r->email,
                'sent_at'           => null,
                'status'            => 'pending',
            ];
        }

        DB::connection('mailing_db')->table('sends')->insert($inserts);

        $this->warn(
            "[RETRY] Creado retry #{$retry->id} con " . count($inserts) .
            " resagados. start_in={$retry->start_in}"
        );
    }

    /**
     * ÚNICO lugar a tocar cuando llegue el método real.
     */
    private function sendOrLog(array $payload): array
    {
        $to = trim((string) ($payload['to_email'] ?? ''));

        $this->line(
            "[SEND] schedule={$payload['schedule_id']} send={$payload['send_id']} " .
            "to=\"{$payload['to_name']}\" <{$to}> subject=\"{$payload['subject']}\""
        );

        if ($to === '') {
            return ['success' => false, 'message' => 'email vacío'];
        }

        return ['success' => true, 'message' => 'simulated'];
    }
}
