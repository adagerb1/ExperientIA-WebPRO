<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solution extends Model
{
    protected $guarded = [];

    protected $casts = [
        'titulo' => 'array',
        'pilar' => 'array',
        'problema' => 'array',
        'como' => 'array',
        'cambia' => 'array',
        'active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('active', true)->orderBy('sort');
    }
}
