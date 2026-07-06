<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'nombre' => 'array',
        'rol' => 'array',
        'texto' => 'array',
        'destacado' => 'boolean',
        'active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('active', true)->orderBy('sort');
    }
}
