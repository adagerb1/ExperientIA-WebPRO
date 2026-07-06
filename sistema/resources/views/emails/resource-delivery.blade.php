<x-mail::message>
@if($locale === 'en')
# Your resource is ready

Here is **{{ tr($resource->titulo, 'en') }}**. The link is valid for 7 days.
@elseif($locale === 'pt')
# Seu recurso está pronto

Aqui está **{{ tr($resource->titulo, 'pt') }}**. O link é válido por 7 dias.
@else
# Su recurso está listo

Aquí tiene **{{ tr($resource->titulo, 'es') }}**. El enlace es válido por 7 días.
@endif

<x-mail::button :url="$downloadUrl">
{{ ['es' => 'Descargar', 'en' => 'Download', 'pt' => 'Baixar'][$locale] ?? 'Descargar' }}
</x-mail::button>

ExperientIA · Automatización · Growth · IA
</x-mail::message>
