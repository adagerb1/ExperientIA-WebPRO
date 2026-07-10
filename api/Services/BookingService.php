<?php
namespace Services;

use Core\Database;
use Core\Env;

/** Cálculo de horarios disponibles y creación de reservas (con Google Calendar). */
final class BookingService
{
    public static function slots(): array
    {
        $cfg = biz('booking');
        $tz = new \DateTimeZone($cfg['timezone']);
        $utc = new \DateTimeZone('UTC');
        $slotMin = (int) $cfg['slot_minutes'];
        $minInicio = (new \DateTimeImmutable('now', $utc))->modify("+{$cfg['min_hours_ahead']} hours");

        $reglas = Database::pdo()->query('SELECT weekday, start_time, end_time FROM availability_rules WHERE active = 1')->fetchAll();
        if (! $reglas) { return []; }
        $porDia = [];
        foreach ($reglas as $r) { $porDia[(int) $r['weekday']][] = $r; }

        $tomadas = Database::run("SELECT starts_at FROM bookings WHERE status = 'confirmada' AND starts_at >= ?", [now_utc()])->fetchAll(\PDO::FETCH_COLUMN);
        $tomadas = array_flip(array_map(fn ($s) => (new \DateTimeImmutable($s, $utc))->format('Y-m-d\TH:i:s\Z'), $tomadas));

        $slots = [];
        $dia = new \DateTimeImmutable('today', $tz);
        for ($i = 0; $i <= (int) $cfg['days_ahead']; $i++) {
            $fecha = $dia->modify("+{$i} days");
            foreach ($porDia[(int) $fecha->format('N')] ?? [] as $regla) {
                $ini = $fecha->modify(substr($regla['start_time'], 0, 5));
                $fin = $fecha->modify(substr($regla['end_time'], 0, 5));
                for ($t = $ini; $t->modify("+{$slotMin} min") <= $fin; $t = $t->modify("+{$slotMin} min")) {
                    $u = $t->setTimezone($utc);
                    if ($u < $minInicio) { continue; }
                    $iso = $u->format('Y-m-d\TH:i:s\Z');
                    if (! isset($tomadas[$iso])) { $slots[] = $iso; }
                }
            }
        }
        sort($slots);
        return $slots;
    }

    public static function isAvailable(string $iso): bool
    {
        return in_array($iso, self::slots(), true);
    }
}
