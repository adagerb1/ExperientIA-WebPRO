<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;

/** Confirmación de sesión 1:1 para el visitante. */
class BookingConfirmation extends Mailable
{
    public function __construct(public Booking $booking, public string $visitorLocale = 'es') {}

    public function build(): static
    {
        $subjects = [
            'es' => 'Su sesión 1:1 con ExperientIA está confirmada',
            'en' => 'Your 1:1 session with ExperientIA is confirmed',
            'pt' => 'Sua sessão 1:1 com a ExperientIA está confirmada',
        ];

        return $this
            ->subject($subjects[$this->visitorLocale] ?? $subjects['es'])
            ->markdown('emails.booking-confirmation', ['locale' => $this->visitorLocale]);
    }
}
