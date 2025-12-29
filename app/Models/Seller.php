<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SellerPackage;

class Seller extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'town',
        'sellerplace',
        'address',
        'description',
        'alias',
        'product_count',
        'region_id',
        'link',
        'link_map',
        'telegram',
        'instagram',
        'viber',
        'photos',
        'logo',
        'balance',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    } 

        /**
     * Связь с пакетами через таблицу seller_packages
     */
    public function sellerPackages()
    {
        return $this->hasMany(SellerPackage::class);
    }

    /**
     * Получить активный пакет продавца
     */
    public function activePackage()
    {
        return $this->sellerPackages()
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('end_date')              // Бессрочный пакет
                    ->orWhere('end_date', '>=', now());  // Или срок еще не истек
            })
            ->with('package')
            ->first();
    }
}
