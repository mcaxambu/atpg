<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'title',
        'slug',
        'subtitle',
        'description',
        'image_path',
        'link_url',
        'button_label',
        'position',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }
}
