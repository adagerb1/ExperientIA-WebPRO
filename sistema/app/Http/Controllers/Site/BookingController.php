<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmation;
use App\Models\Booking;
use App\Services\BookingSlots;
use App\Services\LeadCapture;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function show(BookingSlots $slots)
    {
        return view('site.agenda', ['slots' => $slots->available()]);
    }

    public function store(Request $request, BookingSlots $slots, LeadCapture $capture)
    {
        $data = $request->validate([
            'slot' => ['required', 'date'],
            'timezone' => ['nullable', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone_wa' => ['nullable', 'string', 'max:20'],
            'phone_dial' => ['nullable', 'string', 'max:5'],
            'country' => ['required', 'string', 'size:2'],
            'company' => ['nullable', 'string', 'max:160'],
            'tema' => ['nullable', 'string', 'max:2000'],
            'website' => ['prohibited'],
        ]);

        $slotIso = CarbonImmutable::parse($data['slot'])->utc()->toIso8601ZuluString();

        if (! $slots->isAvailable($slotIso)) {
            return back()->withErrors(['slot' => __('site.agenda.reservado')])->withInput();
        }

        $start = CarbonImmutable::parse($slotIso);

        $lead = $capture->capture(
            $data + ['locale' => app()->getLocale()],
            'reserva',
            'Agendó sesión 1:1 · ' . $start->timezone(config('experientia.booking.timezone'))->isoFormat('D MMM YYYY HH:mm'),
            ['tema' => $data['tema'] ?? ''],
        );

        $booking = Booking::create([
            'lead_id' => $lead->id,
            'starts_at' => $start,
            'ends_at' => $start->addMinutes((int) config('experientia.booking.slot_minutes')),
            'status' => 'confirmada',
            'visitor_timezone' => $data['timezone'] ?? null,
            'tema' => $data['tema'] ?? null,
        ]);

        try {
            Mail::to($lead->email)->send(new BookingConfirmation($booking, app()->getLocale()));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar la confirmación de reserva: ' . $e->getMessage());
        }

        ContactController::notifyAdmin($lead);

        return redirect()->to(lroute('agenda') . '?ok=1');
    }
}
