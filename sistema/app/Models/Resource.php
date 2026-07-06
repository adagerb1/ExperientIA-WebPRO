<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tipo_label' => 'array',
        'titulo' => 'array',
        'extracto' => 'array',
        'cuerpo' => 'array',
        'active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopeActive($q)
    {
        return $q->where('active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('sort');
    }

    public function isDownload(): bool
    {
        return $this->type === 'download';
    }
}
