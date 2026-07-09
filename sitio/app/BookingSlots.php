<?php
/**
 * Horarios disponibles para sesiones 1:1 a partir de las reglas semanales,
 * descontando reservas confirmadas. Devuelve instantes UTC (ISO 8601);
 * el navegador del visitante los muestra en su zona horaria.
 */

function slots_disponibles(): array
{
    $cfg = config('booking');
    $tz = new DateTimeZone($cfg['timezone']);
    $utc = new DateTimeZone('UTC');
    $slotMin = (int) $cfg['slot_minutes'];
    $minInicio = (new DateTimeImmutable('now', $utc))->modify("+{$cfg['min_hours_ahead']} hours");

    $reglas = db()->query('SELECT weekday, start_time, end_time FROM availability_rules WHERE active = 1')->fetchAll();
    if (! $reglas) {
        return [];
    }
    $porDia = [];
    foreach ($reglas as $r) {
        $porDia[(int) $r['weekday']][] = $r;
    }

    $tomadas = db()->query("SELECT starts_at FROM bookings WHERE status = 'confirmada' AND starts_at >= '" . ahora() . "'")
        ->fetchAll(PDO::FETCH_COLUMN);
    $tomadas = array_flip(array_map(
        fn ($s) => (new DateTimeImmutable($s, $utc))->format('Y-m-d\TH:i:s\Z'),
        $tomadas
    ));

    $slots = [];
    $dia = new DateTimeImmutable('today', $tz);

    for ($i = 0; $i <= (int) $cfg['days_ahead']; $i++) {
        $fecha = $dia->modify("+{$i} days");
        $weekday = (int) $fecha->format('N');
        foreach ($porDia[$weekday] ?? [] as $regla) {
            $inicio = $fecha->modify(substr($regla['start_time'], 0, 5));
            $fin = $fecha->modify(substr($regla['end_time'], 0, 5));
            for ($t = $inicio; $t->modify("+{$slotMin} minutes") <= $fin; $t = $t->modify("+{$slotMin} minutes")) {
                $enUtc = $t->setTimezone($utc);
                if ($enUtc < $minInicio) {
                    continue;
                }
                $iso = $enUtc->format('Y-m-d\TH:i:s\Z');
                if (! isset($tomadas[$iso])) {
                    $slots[] = $iso;
                }
            }
        }
    }

    sort($slots);
    return $slots;
}

function slot_disponible(string $isoUtc): bool
{
    return in_array($isoUtc, slots_disponibles(), true);
}
