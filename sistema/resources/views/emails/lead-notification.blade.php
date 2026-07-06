<x-mail::message>
# Nueva interacción: {{ \App\Models\Touchpoint::TYPES[$touchpoint->type] ?? $touchpoint->type }}

**Lead:** {{ $lead->name }}@if($lead->company) · {{ $lead->company }}@endif

- **Correo:** {{ $lead->email ?? '—' }}
- **WhatsApp:** @if($lead->phone_wa)[+{{ $lead->phone_wa }}](https://wa.me/{{ $lead->phone_wa }})@else — @endif
- **País:** {{ $lead->country ?? '—' }} · **Industria:** {{ config('experientia.industries.' . $lead->industry, $lead->industry ?? '—') }}
- **Tamaño:** {{ \App\Models\Lead::SIZES[$lead->company_size] ?? '—' }} · **Idioma:** {{ strtoupper($lead->locale) }}

**Detalle:** {{ $touchpoint->title }}

@if($touchpoint->payload)
@foreach($touchpoint->payload as $k => $v)
@if(is_scalar($v))
- **{{ ucfirst($k) }}:** {{ $v }}
@endif
@endforeach
@endif

<x-mail::button :url="route('filament.admin.resources.leads.edit', $lead)">
Ver en el CRM
</x-mail::button>

ExperientIA · Automatización · Growth · IA
</x-mail::message>
