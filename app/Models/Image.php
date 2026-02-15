<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Image extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'path',
        'is_main',
    ];
    
    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    /**
     * При удалении изображения может измениться активность товара
     */
    protected static function booted()
    {
        static::deleted(function ($image) {
            $product = $image->product;
            
            if (!$product) return;
            
            // Если товар активен и у него больше нет изображений
            $remainingImages = $product->images()->count();
            
            if ($product->is_active && $remainingImages === 0) {
                // Товар станет невидимым - очищаем кеши
                \App\Models\Product::clearProductCaches($product->category);
                \App\Models\Seller::clearSellersCache($product->seller);
            }
        });
    }
}
