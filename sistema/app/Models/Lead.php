<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'nuevo' => 'Nuevo',
        'contactado' => 'Contactado',
        'calificado' => 'Calificado',
        'propuesta' => 'Propuesta',
        'cliente' => 'Cliente',
        'descartado' => 'Descartado',
    ];

    public const SIZES = [
        'micro' => '1–10 (Micro)',
        'pequena' => '11–50 (Pequeña)',
        'mediana' => '51–200 (Mediana)',
        'grande' => '201–1000 (Grande)',
        'corporativa' => '1000+ (Corporativa)',
    ];

    public function touchpoints(): HasMany
    {
        return $this->hasMany(Touchpoint::class)->latest();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Enlace de WhatsApp del lead (wa.me sin el "+"). */
    public function whatsappUrl(): ?string
    {
        return $this->phone_wa ? "https://wa.me/{$this->phone_wa}" : null;
    }

    /** Teléfono para mostrar, con el "+" visible. */
    public function phoneDisplay(): ?string
    {
        return $this->phone_wa ? "+{$this->phone_wa}" : null;
    }
}
