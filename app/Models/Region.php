<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Region $region): void {
            if ($region->settlements()->exists()) {
                throw new \LogicException('Нельзя удалить регион с населёнными пунктами.');
            }
        });
    }

    protected $fillable = [
        'name',
        'type',
        'slug',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
