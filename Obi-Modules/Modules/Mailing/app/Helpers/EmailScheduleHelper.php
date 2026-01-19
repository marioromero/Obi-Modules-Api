<?php

namespace Modules\Mailing\app\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmailScheduleHelper
{
    private const TZ = 'America/Santiago';

    private const WINDOW_START = '08:00:00';
    private const WINDOW_END   = '20:00:00';

    private const INTERVAL_SECONDS = 25;

    public static function buildPreview(int $customerSetId, int $emailTemplateId, string $startIn, bool $onlyBusinessDays): array
    {
        $set = DB::connection('mailing_db')
            ->table('customers_sets')
            ->select(['id', 'name'])
            ->where('id', $customerSetId)
            ->first();

        if (! $set) {
            throw new \InvalidArgumentException('Set de clientes no existe.');
        }

        $recipientsCount = (int) DB::connection('mailing_db')
            ->table('customer_detail')
            ->where('customer_set_id', $customerSetId)
            ->count();

        $tpl = DB::connection('mailing_db')
            ->table('email_templates as t')
            ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
            ->select([
                't.id',
                't.name as template_name',
                'd.email as department_email',
            ])
            ->where('t.id', $emailTemplateId)
            ->first();

        if (! $tpl) {
            throw new \InvalidArgumentException('Plantilla no existe.');
        }

        $startAt = self::parseStartIn($startIn);

        [$endingAt, $timeText] = self::estimate($startAt, $recipientsCount, $onlyBusinessDays);

        return [
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
        ];
    }

    /**
     * ✅ Duración TOTAL de campaña:
     * - incluye envíos (N * 25s)
     * - incluye espera entre días (noche)
     * - si onlyBusinessDays=true, incluye saltos por sábado/domingo/feriado
     */
    public static function estimate(Carbon $startIn, int $recipientsCount, bool $onlyBusinessDays): array
    {
        $startIn = $startIn->copy()->setTimezone(self::TZ);

        $windowSeconds  = 12 * 60 * 60; // 12h
        $capacityPerDay = (int) floor($windowSeconds / self::INTERVAL_SECONDS);
        $capacityPerDay = max(1, $capacityPerDay);

        $remaining = $recipientsCount;

        // Cursor: primer instante válido donde se puede empezar a enviar (08:00-20:00 y hábil si aplica)
        $cursor = self::normalizeToWindowStart($startIn, $onlyBusinessDays);

        // Duración total real de campaña, desde cursor, incluyendo esperas
        $totalDurationSeconds = 0;

        while ($remaining > 0) {

            // Si solo hábiles, saltar no-hábiles sumando espera
            if ($onlyBusinessDays && !ChileBusinessDays::isBusinessDay($cursor)) {
                $next = $cursor->copy()->addDay()->setTimeFromTimeString(self::WINDOW_START);
                $totalDurationSeconds += $next->diffInSeconds($cursor);
                $cursor = $next;
                continue;
            }

            $sentToday = min($capacityPerDay, $remaining);
            $remaining -= $sentToday;

            // segundos de envío este día
            $sendSecondsToday = $sentToday * self::INTERVAL_SECONDS;
            $totalDurationSeconds += $sendSecondsToday;

            // termina hoy
            if ($remaining <= 0) {
                $endingAt = $cursor->copy()->addSeconds($sendSecondsToday);

                $timeText = self::humanDurationDetailed($totalDurationSeconds)
                    . ' (termina ' . $endingAt->format('Y-m-d H:i:s') . ')';

                return [$endingAt, $timeText];
            }

            // pasar al siguiente día 08:00 + sumar espera (noche y saltos)
            $nextDayStart = $cursor->copy()->addDay()->setTimeFromTimeString(self::WINDOW_START);

            if ($onlyBusinessDays) {
                while (!ChileBusinessDays::isBusinessDay($nextDayStart)) {
                    $after = $nextDayStart->copy()->addDay()->setTimeFromTimeString(self::WINDOW_START);
                    $totalDurationSeconds += $after->diffInSeconds($nextDayStart);
                    $nextDayStart = $after;
                }
            }

            $totalDurationSeconds += $nextDayStart->diffInSeconds($cursor); // espera entre días
            $cursor = $nextDayStart;
        }

        // fallback
        $endingAt = $cursor;
        $timeText = self::humanDurationDetailed($totalDurationSeconds)
            . ' (termina ' . $endingAt->format('Y-m-d H:i:s') . ')';

        return [$endingAt, $timeText];
    }

    private static function normalizeToWindowStart(Carbon $startIn, bool $onlyBusinessDays): Carbon
    {
        $date = $startIn->copy()->setTimezone(self::TZ);

        $startT = Carbon::parse($date->format('Y-m-d') . ' ' . self::WINDOW_START, self::TZ)->setTimezone(self::TZ);
        $endT   = Carbon::parse($date->format('Y-m-d') . ' ' . self::WINDOW_END, self::TZ)->setTimezone(self::TZ);

        if ($date->lt($startT)) {
            $date = $startT;
        } elseif ($date->gte($endT)) {
            $date = $startT->addDay();
        }

        if ($onlyBusinessDays) {
            while (!ChileBusinessDays::isBusinessDay($date)) {
                $date = $date->addDay()->setTimeFromTimeString(self::WINDOW_START);
            }
        }

        return $date;
    }

    private static function humanDurationDetailed(int $seconds): string
    {
        $seconds = max(0, $seconds);

        $days = intdiv($seconds, 86400);
        $seconds -= $days * 86400;

        $hours = intdiv($seconds, 3600);
        $seconds -= $hours * 3600;

        $mins = intdiv($seconds, 60);
        $seconds -= $mins * 60;

        $parts = [];
        if ($days > 0)  $parts[] = $days . ' día' . ($days !== 1 ? 's' : '');
        if ($hours > 0) $parts[] = $hours . ' hora' . ($hours !== 1 ? 's' : '');
        if ($mins > 0)  $parts[] = $mins . ' min';

        // siempre mostrar segundos si hay, o si no hay nada (para evitar "0 min" ambiguo)
        if ($seconds > 0 || empty($parts)) {
            $parts[] = $seconds . ' seg';
        }

        return implode(' ', $parts);
    }

    private static function parseStartIn(string $value): Carbon
    {
        // Si viene solo fecha (YYYY-MM-DD), forzar 08:00 hora Chile
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::parse($value . ' ' . self::WINDOW_START, self::TZ)->setTimezone(self::TZ);
        }

        return Carbon::parse($value, self::TZ)->setTimezone(self::TZ);
    }

    public static function resolveStartAndEnd(string $startIn, int $recipientsCount, bool $onlyBusinessDays): array
    {
        $startAt = self::parseStartIn($startIn);

        $startAtNormalized = self::normalizeToWindowStart($startAt, $onlyBusinessDays);

        [$endingAt, $timeText] = self::estimate($startAtNormalized, $recipientsCount, $onlyBusinessDays);

        return [$startAtNormalized, $endingAt, $timeText];
    }
}
