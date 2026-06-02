<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property float $price
 * @property int $duration_days
 * @property int $active_products_limit
 * @property int $products_limit Alias for active_products_limit
 * @property int $vip_products_limit
 * @property bool $has_website_link
 * @property bool $has_social_links
 * @property bool $has_order_system
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SellerPackage> $sellerPackages
 */
class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'active_products_limit',
        'vip_products_limit',
        'has_website_link',
        'has_social_links',
        'has_order_system',
        'is_active',
    ];

    protected $casts = [
        'has_website_link' => 'boolean',
        'has_social_links' => 'boolean',
        'has_order_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Подписки продавцов на этот пакет
     */
    public function sellerPackages(): HasMany
    {
        return $this->hasMany(SellerPackage::class);
    }
}
