<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $guarded = [];

    protected $casts = [
        'pregunta' => 'array',
        'respuesta' => 'array',
        'active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('active', true)->orderBy('sort');
    }
}
