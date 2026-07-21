<?php

namespace App\Models;

use App\Enums\ProductModerationStatus;
use App\Services\CatalogCacheService;
use App\Services\ProductVisibilityResolver;
use App\Services\ProductViewStateFactory;
use App\Services\RegionalContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity, Searchable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'lowprice', 'is_vip', 'is_active', 'publication_requested', 'category_id', 'seller_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('products')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => 'Товар создан: "'.$this->name.'"',
                'updated' => 'Товар обновлён: "'.$this->name.'"',
                'deleted' => 'Товар удалён: "'.$this->name.'"',
                default => $eventName,
            });
    }

    protected $fillable = [
        'name',
        'price',
        'lowprice',
        'desc',
        'features1',
        'features2',
        'features3',
        'features4',
        'alias',
        'is_vip',
        'is_active',
        'publication_requested',
        'can_order',
        'delivery_enabled',
        'category_id',
        'seller_id',
        'sale_started_at',
        'push_at',
    ];

    protected $guarded = [
        'id',

    ];

    protected $casts = [
        'push_at' => 'datetime',
        'sale_started_at' => 'datetime',
        'moderation_status' => ProductModerationStatus::class,
        'moderation_version' => 'integer',
        'moderated_version' => 'integer',
        'moderation_risk_score' => 'decimal:4',
        'moderation_submitted_at' => 'datetime',
        'moderated_at' => 'datetime',
        'is_active' => 'boolean',
        'publication_requested' => 'boolean',
        'can_order' => 'boolean',
        'delivery_enabled' => 'boolean',
    ];

    public function searchableAs(): string
    {
        return 'products';
    }

    public function shouldBeSearchable(): bool
    {
        return $this->moderation_status === ProductModerationStatus::Approved
            && $this->moderated_version === $this->moderation_version
            && $this->is_active
            && $this->seller?->activePackage() !== null;
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['category', 'seller.settlement']);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->desc,
            'features' => array_values(array_filter([
                $this->features1,
                $this->features2,
                $this->features3,
                $this->features4,
            ])),
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'seller_id' => $this->seller_id,
            'seller_name' => $this->seller?->name,
            'settlement_id' => $this->seller?->settlement_id,
            'is_vip' => (int) $this->is_vip,
            'push_at' => $this->push_at?->getTimestamp() ?? 0,
        ];
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function seller()
    {
        return $this->belongsTo(\App\Models\Seller::class);
    }

    public function images()
    {
        return $this->hasMany(\App\Models\Image::class);
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(Image::class)
            ->where('is_main', true)
            ->orderBy('order');
    }

    public function moderationRuns(): HasMany
    {
        return $this->hasMany(ProductModerationRun::class);
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(ProductModerationAction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function moderatedBy()
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }

    public function getDisplayStatusAttribute(): string
    {
        if (
            $this->moderation_status === ProductModerationStatus::Approved
            && $this->moderated_version === $this->moderation_version
        ) {
            return $this->is_active ? 'Опубликован' : 'Снят с публикации';
        }

        return $this->moderation_status->label();
    }

    public function getViewStateAttribute(): \App\Data\ProductViewState
    {
        return app(ProductViewStateFactory::class)->make($this);
    }

    public function getCabinetStateAttribute(): \App\Data\ProductCabinetState
    {
        return app(\App\Services\ProductCabinetStateService::class)->make($this);
    }

    public function getCabinetStateSignatureAttribute(): string
    {
        $state = $this->cabinet_state;

        return sha1(implode('|', [
            $state->status,
            $state->label,
            $state->color,
            (int) $state->enabledBySeller,
            (int) $state->publicationRequested,
            (int) $state->moderationActual,
            (int) $state->hasImages,
            (int) $state->hasActivePackage,
            (int) $state->withinActiveLimit,
            (int) $state->withinVipLimit,
            (int) $state->canBeOrdered,
            (int) $state->visibleToBuyers,
            (string) ($state->hiddenReason ?? ''),
            implode(',', $state->secondaryReasons),
            (string) ($state->primaryAction ?? ''),
            (string) ($state->primaryActionLabel ?? ''),
            (string) ($this->moderation_status?->value ?? ''),
            (string) ($this->moderation_version ?? ''),
            (string) ($this->moderated_version ?? ''),
            (int) $this->is_active,
            (int) $this->publication_requested,
            (string) ($this->push_at?->timestamp ?? ''),
            (string) ($this->updated_at?->timestamp ?? ''),
        ]));
    }

    /**
     * Scope: только товары от продавцов с активными пакетами
     */
    public function scopeFromActiveSellerPackage($query)
    {
        return $query->whereHas('seller.sellerPackages', function ($q) {
            $q->where('is_active', true)
                ->where(function ($dateQuery) {
                    $dateQuery->whereNull('end_date')
                        ->orWhere('end_date', '>=', now()->toDateString()); // DATE vs DATE, не DATETIME
                });
        });
    }

    /**
     * Scope: публичные товары
     * (продавец активировал И у продавца есть активный пакет)
     */
    public function scopeVisible($query)
    {
        return app(ProductVisibilityResolver::class)->applyPublicVisibility($query);
    }

    public function scopeForRegionalContext(Builder $query, ?RegionalContext $context = null): Builder
    {
        $context ??= app(RegionalContext::class);
        $settlement = $context->settlement();

        return $query->when($settlement, function (Builder $regionalQuery) use ($settlement) {
            $regionalQuery->whereHas(
                'seller',
                fn (Builder $sellerQuery) => $sellerQuery->where('settlement_id', $settlement->id)
            );
        });
    }

    // ==========================================
    // РАБОТА С КЕШЕМ
    // ==========================================

    /**
     * Очистить все кеши, связанные с товарами
     */
    public static function clearProductCaches($category = null, $oldCategoryId = null)
    {
        app(CatalogCacheService::class)->forgetProductCatalog($category, $oldCategoryId);
    }

    /**
     * Получить закешированное количество товаров в категории (включая подкатегории)
     */
    public static function getCategoryProductsCount($categoryId)
    {
        return app(CatalogCacheService::class)->rememberCategoryProductsCount((int) $categoryId, function () use ($categoryId) {
            $category = \App\Models\Category::find($categoryId);
            if (! $category) {
                return 0;
            }

            $categoryIds = \App\Models\Category::whereDescendantOrSelf($categoryId)->pluck('id');

            return self::visible()
                ->forRegionalContext()
                ->whereIn('category_id', $categoryIds)
                ->count();
        });
    }

    /**
     * Получить закешированный диапазон цен товаров
     */
    public static function getCachedPriceRange()
    {
        return app(CatalogCacheService::class)->rememberProductPriceRange(function () {
            return [
                'min' => self::visible()->forRegionalContext()->min('price') ?? 0,
                'max' => self::visible()->forRegionalContext()->max('price') ?? 0,
            ];
        });
    }
}
