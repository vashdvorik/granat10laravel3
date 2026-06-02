<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $path
 * @property bool $is_main
 * @property string|null $original_path
 * @property string|null $thumb_path
 * @property string|null $large_path
 * @property int|null $size
 * @property string|null $mime_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 */
class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'is_main',
    ];

    /**
     * Товар, к которому принадлежит изображение
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * При удалении изображения может измениться активность товара
     */
    protected static function booted()
    {
        static::deleted(function ($image) {
            $product = $image->product;

            if (! $product) {
                return;
            }

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
