<?php

namespace App\Models;

use App\Support\RussianLocative;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Settlement $settlement): void {
            if ($settlement->sellers()->exists()) {
                throw new \LogicException('Нельзя удалить населённый пункт с привязанными продавцами.');
            }
        });
    }

    protected $fillable = [
        'region_id',
        'name',
        'type',
        'zip',
        'slug',
        'name_in',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** «Тирасполе»: предложный падеж для заголовков (задан вручную в админке или подобран автоматически). */
    public function nameIn(): string
    {
        $manual = trim((string) $this->name_in);

        return $manual !== '' ? $manual : RussianLocative::prepositional((string) $this->name);
    }

    /** «в Тирасполе» — готовая фраза с предлогом. */
    public function inPhrase(): string
    {
        $manual = trim((string) $this->name_in);

        if ($manual === '') {
            return RussianLocative::in((string) $this->name);
        }

        return (preg_match('/^[вф][бвгджзйклмнпрстфхцчшщ]/iu', $manual) ? 'во ' : 'в ').$manual;
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function sellers(): HasMany
    {
        return $this->hasMany(Seller::class);
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
