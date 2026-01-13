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
        
    ];
    protected $guarded = [
    'id',
    'balance',      // Только система меняет баланс
    'product_count', // Автоматически считается
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

    // ============================================
    // ПРОВЕРКА НАЛИЧИЯ МЕСТ В ТАРИФНЫХ ПАКЕТАХ
    // ============================================

    /**
     * Получить текущее количество активных товаров
     */
    public function getActiveProductsCount(): int
    {
        return $this->products()->where('is_active', true)->count();
    }

    /**
     * Получить текущее количество активных VIP товаров
     */
    public function getVipProductsCount(): int
    {
        return $this->products()
            ->where('is_active', true)
            ->where('is_vip', true)
            ->count();
    }

    /**
     * Проверить, можно ли активировать товар (обычный)
     * 
     * @param int|null $excludeProductId ID товара, который исключаем из подсчета (при редактировании)
     * @return array ['allowed' => bool, 'message' => string, 'current' => int, 'limit' => int]
     */
    public function canActivateProduct(?int $excludeProductId = null): array
    {
        $activePackage = $this->activePackage();
        
        if (!$activePackage) {
            return [
                'allowed' => false,
                'message' => 'У вас нет активного тарифного пакета',
                'current' => 0,
                'limit' => 0,
            ];
        }
        
        $package = $activePackage->package;
        $limit = $package->active_products_limit;
        
        // Подсчитываем текущие активные товары (исключая редактируемый)
        $query = $this->products()->where('is_active', true);
        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }
        $current = $query->count();
        
        $allowed = $current < $limit;
        
        return [
            'allowed' => $allowed,
            'message' => $allowed 
                ? 'Можно активировать товар' 
                : "Достигнут лимит активных товаров ({$current}/{$limit}). Обновите тариф.",
            'current' => $current,
            'limit' => $limit,
            'package_name' => $package->name,
        ];
    }

    /**
     * Проверить, можно ли активировать VIP товар
     * 
     * @param int|null $excludeProductId ID товара, который исключаем из подсчета (при редактировании)
     * @return array ['allowed' => bool, 'message' => string, 'current' => int, 'limit' => int]
     */
    public function canActivateVipProduct(?int $excludeProductId = null): array
    {
        $activePackage = $this->activePackage();
        
        if (!$activePackage) {
            return [
                'allowed' => false,
                'message' => 'У вас нет активного тарифного пакета',
                'current' => 0,
                'limit' => 0,
            ];
        }
        
        $package = $activePackage->package;
        $limit = $package->vip_products_limit;
        
        // Подсчитываем текущие VIP товары (исключая редактируемый)
        $query = $this->products()
            ->where('is_active', true)
            ->where('is_vip', true);
        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }
        $current = $query->count();
        
        $allowed = $current < $limit;
        
        return [
            'allowed' => $allowed,
            'message' => $allowed 
                ? 'Можно активировать VIP товар' 
                : "Достигнут лимит VIP товаров ({$current}/{$limit}). Обновите тариф.",
            'current' => $current,
            'limit' => $limit,
            'package_name' => $package->name,
        ];
    }

    /**
     * Проверить все лимиты для товара
     * 
     * @param bool $isActive Будет ли товар активным
     * @param bool $isVip Будет ли товар VIP
     * @param int|null $excludeProductId ID редактируемого товара
     * @return array ['allowed' => bool, 'errors' => array]
     */
    public function checkProductLimits(bool $isActive, bool $isVip, ?int $excludeProductId = null): array
    {
        $errors = [];
        
        // Если товар неактивен, можно добавлять сколько угодно
        if (!$isActive) {
            return ['allowed' => true, 'errors' => []];
        }
        
        // Проверяем лимит активных товаров
        $activeCheck = $this->canActivateProduct($excludeProductId);
        if (!$activeCheck['allowed']) {
            $errors[] = $activeCheck['message'];
        }
        
        // Если товар VIP, проверяем лимит VIP товаров
        if ($isVip) {
            $vipCheck = $this->canActivateVipProduct($excludeProductId);
            if (!$vipCheck['allowed']) {
                $errors[] = $vipCheck['message'];
            }
        }
        
        return [
            'allowed' => empty($errors),
            'errors' => $errors,
        ];
    }
}
