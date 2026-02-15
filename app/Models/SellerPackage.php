<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SellerPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'package_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Связь с продавцом
     */
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
    /**
     * Связь с пакетом
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * При изменении пакета меняется видимость товаров продавца
     * Необходимо очистить все связанные кеши
     */
    protected static function booted()
    {
        static::saved(function ($sellerPackage) {
            // Очистить кеш продавца
            \App\Models\Seller::clearSellersCache($sellerPackage->seller);
            
            // Очистить кеш диапазона цен (видимость товаров изменилась)
            Cache::forget('product_price_range');
            
            // Очистить счетчики категорий (товары стали видимы/невидимы)
            \App\Models\Category::clearCategoriesCache();
        });

        static::deleted(function ($sellerPackage) {
            // Аналогичная очистка при удалении пакета
            \App\Models\Seller::clearSellersCache($sellerPackage->seller);
            Cache::forget('product_price_range');
            \App\Models\Category::clearCategoriesCache();
        });
    }
}
