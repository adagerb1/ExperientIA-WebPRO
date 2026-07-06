<?php

namespace App\Mail;

use App\Models\Lead;
use App\Models\Touchpoint;
use Illuminate\Mail\Mailable;

/** Aviso interno: nuevo lead o nueva interacción en la plataforma. */
class LeadNotification extends Mailable
{
    public function __construct(
        public Lead $lead,
        public Touchpoint $touchpoint,
    ) {}

    public function build(): static
    {
        $type = Touchpoint::TYPES[$this->touchpoint->type] ?? $this->touchpoint->type;

        return $this
            ->subject("[ExperientIA] {$type}: {$this->lead->name}" . ($this->lead->company ? " · {$this->lead->company}" : ''))
            ->markdown('emails.lead-notification');
    }
}
