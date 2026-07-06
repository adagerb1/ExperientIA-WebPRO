<?php

namespace App\Mail;

use App\Models\Resource;
use Illuminate\Mail\Mailable;

/** Entrega de recurso descargable con enlace firmado. */
class ResourceDelivery extends Mailable
{
    public function __construct(
        public Resource $resource,
        public string $downloadUrl,
        public string $visitorLocale = 'es',
    ) {}

    public function build(): static
    {
        $subjects = [
            'es' => 'Su recurso de ExperientIA: ',
            'en' => 'Your ExperientIA resource: ',
            'pt' => 'Seu recurso da ExperientIA: ',
        ];

        return $this
            ->subject(($subjects[$this->visitorLocale] ?? $subjects['es']) . tr($this->resource->titulo, $this->visitorLocale))
            ->markdown('emails.resource-delivery', ['locale' => $this->visitorLocale]);
    }
}
