<?php

namespace App\Services;

use App\Models\AvailabilityRule;
use App\Models\Booking;
use Carbon\CarbonImmutable;

/**
 * Calcula los horarios disponibles para sesiones 1:1 a partir de las reglas
 * semanales configuradas en el panel, descontando reservas confirmadas.
 * Devuelve instantes UTC; el navegador del visitante los muestra en su zona.
 */
class BookingSlots
{
    public function available(): array
    {
        $cfg = config('experientia.booking');
        $tz = $cfg['timezone'];
        $slotMinutes = (int) $cfg['slot_minutes'];
        $daysAhead = (int) $cfg['days_ahead'];
        $minStart = CarbonImmutable::now()->addHours((int) $cfg['min_hours_ahead']);

        $rules = AvailabilityRule::where('active', true)->get()->groupBy('weekday');

        if ($rules->isEmpty()) {
            return [];
        }

        $taken = Booking::where('status', 'confirmada')
            ->where('starts_at', '>=', now())
            ->pluck('starts_at')
            ->map(fn ($d) => $d->toIso8601ZuluString())
            ->flip();

        $slots = [];
        $today = CarbonImmutable::now($tz)->startOfDay();

        for ($i = 0; $i <= $daysAhead; $i++) {
            $day = $today->addDays($i);
            foreach ($rules->get($day->isoWeekday(), []) as $rule) {
                $start = $day->setTimeFromTimeString($rule->start_time);
                $end = $day->setTimeFromTimeString($rule->end_time);

                for ($t = $start; $t->addMinutes($slotMinutes)->lte($end); $t = $t->addMinutes($slotMinutes)) {
                    $utc = $t->utc();
                    if ($utc->lt($minStart)) {
                        continue;
                    }
                    $iso = $utc->toIso8601ZuluString();
                    if (! isset($taken[$iso])) {
                        $slots[] = $iso;
                    }
                }
            }
        }

        sort($slots);

        return $slots;
    }

    public function isAvailable(string $isoUtc): bool
    {
        return in_array($isoUtc, $this->available(), true);
    }
}
