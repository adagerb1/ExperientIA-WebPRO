<?php
namespace Controllers\PublicApi;

use Core\Controller;
use Core\RateLimiter;
use Core\Response;
use Core\Validator;
use Core\Database;
use Services\BookingService;
use Services\LeadService;
use Services\Mailer;
use Services\Connectors\GoogleCalendarConnector;

final class BookingController extends Controller
{
    public function slots(): void
    {
        RateLimiter::public($this->req);
        Response::ok(['slots' => BookingService::slots()]);
    }

    public function reservar(): void
    {
        RateLimiter::public($this->req);
        $v = Validator::make($this->req->body)->honeypot()
            ->text('name', true, 160)->email('email', true)->phone('phone_wa')->text('phone_dial', false, 5)
            ->country('country', true)->text('company', false, 160)->textarea('tema', false, 2000)
            ->text('timezone', false, 60);
        $d = $v->failOrValidated();

        $slotRaw = (string) $this->req->input('slot', '');
        try { $inicio = new \DateTimeImmutable($slotRaw, new \DateTimeZone('UTC')); }
        catch (\Throwable) { Response::error('Revise los campos marcados.', 422, ['campos' => ['slot']]); }
        $iso = $inicio->format('Y-m-d\TH:i:s\Z');
        if (! BookingService::isAvailable($iso)) {
            Response::error('Ese horario ya fue reservado. Elija otro.', 409, ['campos' => ['slot']]);
        }

        $d['locale'] = in_array($l = $this->req->input('locale', 'es'), biz('locales'), true) ? $l : 'es';
        $d = array_merge($d, \Core\Attribution::fromRequest($this->req));
        $cfgTz = new \DateTimeZone(biz('booking')['timezone']);
        $lead = LeadService::capture($d, 'reserva',
            'Agendó sesión 1:1 · ' . $inicio->setTimezone($cfgTz)->format('d/m/Y H:i'), ['tema' => $d['tema']]);

        $ends = $inicio->modify('+' . biz('booking')['slot_minutes'] . ' min');
        $gcal = GoogleCalendarConnector::createEvent('Sesión 1:1 · ' . ($d['name']), $iso, $ends->format('Y-m-d\TH:i:s\Z'), $d['tema'] ?: null);

        Database::run('INSERT INTO bookings (lead_id, starts_at, ends_at, status, visitor_timezone, tema, gcal_event_id, created_at) VALUES (?,?,?,?,?,?,?,?)',
            [$lead['id'], $inicio->format('Y-m-d H:i:s'), $ends->format('Y-m-d H:i:s'), 'confirmada', $d['timezone'] ?: null, $d['tema'] ?: null, $gcal, now_utc()]);

        // Confirmación al visitante
        $tzVis = $d['timezone'] ?: biz('booking')['timezone'];
        try { $local = $inicio->setTimezone(new \DateTimeZone($tzVis)); } catch (\Throwable) { $local = $inicio->setTimezone($cfgTz); $tzVis = biz('booking')['timezone']; }
        Mailer::send($d['email'], '[ExperientIA] Su sesión 1:1 está confirmada',
            '<h2>Su sesión está confirmada</h2><p>Hola ' . htmlspecialchars($d['name']) . ', su sesión quedó agendada para <b>' . $local->format('d/m/Y H:i') . '</b> (' . htmlspecialchars($tzVis) . ').</p>');

        Response::ok(['message' => 'ok']);
    }
}
