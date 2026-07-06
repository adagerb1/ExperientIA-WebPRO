<x-mail::message>
@if($locale === 'en')
# Your session is confirmed

Hi {{ $booking->lead->name }}, your 1:1 executive session with ExperientIA is booked.

- **Date and time:** {{ $booking->starts_at->timezone($booking->visitor_timezone ?? config('experientia.booking.timezone'))->isoFormat('dddd D MMMM YYYY, HH:mm') }} ({{ $booking->visitor_timezone ?? config('experientia.booking.timezone') }})
- **Duration:** {{ config('experientia.booking.slot_minutes') }} minutes

We will contact you at this email with the meeting link.
@elseif($locale === 'pt')
# Sua sessão está confirmada

Olá {{ $booking->lead->name }}, sua sessão executiva 1:1 com a ExperientIA está agendada.

- **Data e hora:** {{ $booking->starts_at->timezone($booking->visitor_timezone ?? config('experientia.booking.timezone'))->locale('pt')->isoFormat('dddd D MMMM YYYY, HH:mm') }} ({{ $booking->visitor_timezone ?? config('experientia.booking.timezone') }})
- **Duração:** {{ config('experientia.booking.slot_minutes') }} minutos

Entraremos em contato por este e-mail com o link da reunião.
@else
# Su sesión está confirmada

Hola {{ $booking->lead->name }}, su sesión ejecutiva 1:1 con ExperientIA quedó agendada.

- **Fecha y hora:** {{ $booking->starts_at->timezone($booking->visitor_timezone ?? config('experientia.booking.timezone'))->locale('es')->isoFormat('dddd D MMMM YYYY, HH:mm') }} ({{ $booking->visitor_timezone ?? config('experientia.booking.timezone') }})
- **Duración:** {{ config('experientia.booking.slot_minutes') }} minutos

Le contactaremos a este correo con el enlace de la reunión.
@endif

ExperientIA · Automatización · Growth · IA
</x-mail::message>
