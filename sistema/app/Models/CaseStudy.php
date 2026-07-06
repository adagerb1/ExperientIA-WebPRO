<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sector' => 'array',
        'titulo' => 'array',
        'contexto' => 'array',
        'intervencion' => 'array',
        'resultados' => 'array',
        'active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('active', true)->orderBy('sort');
    }
}
