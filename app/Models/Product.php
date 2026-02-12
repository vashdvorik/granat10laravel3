<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

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
    ];

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

    /**
     * Scope: только товары от продавцов с активными пакетами
     */
    public function scopeFromActiveSellerPackage($query)
    {
        return $query->whereHas('seller.sellerPackages', function ($q) {
            $q->where('is_active', true)
                ->where(function($dateQuery) {
                    $dateQuery->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                });
        });
    }

    /**
     * Scope: публичные товары 
     * (продавец активировал И у продавца есть активный пакет)
     */
    public function scopeVisible($query)
    {
        return $query->where('is_active', true)  // продавец сам активировал
            ->fromActiveSellerPackage();         // и пакет активен
    }
}
