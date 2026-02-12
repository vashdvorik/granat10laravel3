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
        'auto_renew',
        
    ];
    protected $guarded = [
    'id',
    'product_count', // Автоматически считается
    ];
    
    protected $casts = [
    'can_order' => 'boolean',
    'auto_renew' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    
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

    // ============================================
    // УПРАВЛЕНИЕ ПАКЕТАМИ И АВТОПРОДЛЕНИЕ
    // ============================================

    /**
     * Проверить, есть ли активный пакет
     */
    public function hasActivePackage(): bool
    {
        return $this->activePackage() !== null;
    }

    /**
     * Проверить возможность автопродления
     */
    public function canAutoRenew(): bool
    {
        $activePackage = $this->activePackage();
        
        if (!$activePackage) {
            return false;
        }
        
        $package = $activePackage->package;
        
        return $this->user->balance >= $package->price;
    }

    /**
     * Попробовать продлить пакет автоматически
     */
    public function tryAutoRenewPackage(): array
    {
        $activePackage = $this->activePackage();
        
        if (!$activePackage) {
            return ['success' => false, 'message' => 'Нет активного пакета'];
        }
        
        $package = $activePackage->package;
        
        if ($this->user->balance < $package->price) {
            return [
                'success' => false, 
                'message' => "Недостаточно средств. Нужно: {$package->price} руб, баланс: {$this->user->balance} руб"
            ];
        }
        
        // Списываем деньги
        $this->user->decrement('balance', $package->price);
        
        // Продлеваем пакет
        $activePackage->update([
            'start_date' => now(),
            'end_date' => now()->addDays($package->duration_days),
        ]);
        
        return [
            'success' => true,
            'message' => "Пакет успешно продлен до {$activePackage->end_date->format('d.m.Y')}",
            'end_date' => $activePackage->end_date,
        ];
    }

    /**
     * Получить статус пакета для отображения в кабинете
     */
    public function getPackageStatus(): array
    {
        $activePackage = $this->activePackage();
        
        if (!$activePackage) {
            $activeProductsCount = $this->products()->where('is_active', true)->count();
            
            return [
                'has_package' => false,
                'can_auto_renew' => false,
                'message' => 'У вас нет активного пакета',
                'description' => $activeProductsCount > 0 
                    ? "Ваши товары ({$activeProductsCount} шт.) скрыты от покупателей" 
                    : "Приобретите пакет для публикации товаров",
                'alert_type' => 'danger',
            ];
        }
        
        $package = $activePackage->package;
        
        // Бессрочный пакет
        if (!$activePackage->end_date) {
            return [
                'has_package' => true,
                'package_name' => $package->name,
                'message' => 'Бессрочный пакет активен',
                'alert_type' => 'success',
            ];
        }
        
        $daysLeft = now()->diffInDays($activePackage->end_date, false);
        $canAutoRenew = $this->user->balance >= $package->price;
        
        // Просрочен
        if ($daysLeft < 0) {
            return [
                'has_package' => false,
                'can_auto_renew' => $canAutoRenew,
                'message' => 'Пакет просрочен',
                'description' => 'Ваши товары скрыты от покупателей',
                'balance' => $this->user->balance,
                'price' => $package->price,
                'package_name' => $package->name,
                'alert_type' => 'danger',
            ];
        }
        
        // Скоро истекает (меньше 7 дней)
        if ($daysLeft <= 7) {
        $message = "Пакет истекает через {$daysLeft} " . $this->getDaysWord($daysLeft);
        $description = '';
        
        if (!$this->auto_renew) {
            $description = " Автопродление отключено.";
        } elseif (!$canAutoRenew) {
            $description = "❌ Недостаточно средств для автопродления. Нужно {$package->price} руб, баланс: {$this->user->balance} руб";
        } else {
            $description = "Пакет будет автоматически продлен из вашего баланса. Ваш баланс: {$this->user->balance} руб, стоимость продления: {$package->price} руб";
        }
        
        return [
            'has_package' => true,
            'days_left' => $daysLeft,
            'end_date' => $activePackage->end_date,
            'can_auto_renew' => $canAutoRenew && $this->auto_renew,
            'message' => $message,
            'description' => $description,
            'balance' => $this->user->balance,
            'price' => $package->price,
            'package_name' => $package->name,
            'alert_type' => $this->auto_renew && $canAutoRenew ? 'warning' : 'danger',
        ];
    }
        
        // Все хорошо
        return [
            'has_package' => true,
            'days_left' => $daysLeft,
            'end_date' => $activePackage->end_date,
            'package_name' => $package->name,
            'message' => "Пакет \"{$package->name}\" активен",
            'description' => "Действует до {$activePackage->end_date->format('d.m.Y')} ({$daysLeft} дней)",
            'alert_type' => 'info',
        ];
    }

    /**
     * Вспомогательный метод для склонения слова "день"
     */
    private function getDaysWord(int $days): string
    {
        $cases = [2, 0, 1, 1, 1, 2];
        $titles = ['день', 'дня', 'дней'];
        
        return $titles[($days % 100 > 4 && $days % 100 < 20) ? 2 : $cases[min($days % 10, 5)]];
    }
}
