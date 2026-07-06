<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Touchpoint extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    public const TYPES = [
        'contacto' => 'Formulario de contacto',
        'descarga' => 'Descarga de recurso',
        'diagnostico' => 'Diagnóstico',
        'reserva' => 'Reserva de sesión',
        'newsletter' => 'Newsletter',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
