<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'active_products_limit' => 'integer',
        'vip_products_limit' => 'integer',
        'has_website_link' => 'boolean',
        'has_social_links' => 'boolean',
        'has_order_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function sellerPackages()
    {
        return $this->hasMany(SellerPackage::class);
    }
}
