<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Notifications\AccountNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductVipService
{
    public function __construct(
        private readonly VipPricing $pricing,
        private readonly ProductVisibilityResolver $visibilityResolver,
    ) {}

    /**
     * Покупка (или продление) VIP для товара за деньги с баланса продавца.
     */
    public function purchase(User $user, Product $product, int $days): Product
    {
        if (! $this->pricing->isValidDuration($days)) {
            throw new RuntimeException('Выбран недоступный срок VIP.');
        }

        return DB::transaction(function () use ($user, $product, $days): Product {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $lockedProduct->loadMissing('seller');

            if ($lockedProduct->seller === null || (int) $lockedProduct->seller->user_id !== (int) $lockedUser->getKey()) {
                throw new RuntimeException('Этот товар вам не принадлежит.');
            }

            if (! $this->visibilityResolver->isPublic($lockedProduct)) {
                throw new RuntimeException('VIP можно подключить только опубликованному товару, который прошёл проверку.');
            }

            if ($lockedProduct->is_vip && $lockedProduct->vip_until === null) {
                throw new RuntimeException('У этого товара уже действует бессрочный VIP.');
            }

            $priceInCents = $this->pricing->priceInCents($days);
            $balanceInCents = $this->toCents($lockedUser->getRawOriginal('balance') ?? '0');

            if ($balanceInCents < $priceInCents) {
                throw new RuntimeException(
                    'Недостаточно средств на балансе. Нужно '.$this->pricing->format($priceInCents)
                    .' руб, на балансе '.$this->pricing->format($balanceInCents).' руб.'
                );
            }

            $startsFrom = $lockedProduct->isVipActive() && $lockedProduct->vip_until !== null
                ? $lockedProduct->vip_until->copy()
                : now();
            $vipUntil = $startsFrom->addDays($days);
            $newBalanceInCents = $balanceInCents - $priceInCents;

            $lockedUser->forceFill(['balance' => $this->pricing->format($newBalanceInCents)])->save();
            $lockedProduct->forceFill([
                'is_vip' => true,
                'vip_until' => $vipUntil,
            ])->save();

            activity('products')
                ->causedBy($lockedUser)
                ->performedOn($lockedProduct)
                ->withProperties([
                    'action' => 'vip_purchase',
                    'seller_id' => $lockedProduct->seller_id,
                    'days' => $days,
                    'price' => $this->pricing->format($priceInCents),
                    'balance_before' => $this->pricing->format($balanceInCents),
                    'balance_after' => $this->pricing->format($newBalanceInCents),
                    'vip_until' => $vipUntil->toDateTimeString(),
                ])
                ->log('Куплен VIP для товара "'.$lockedProduct->name.'" на '.$days.' дн.');

            return $lockedProduct->refresh();
        });
    }

    /**
     * Ручная установка VIP администратором (без списания с баланса).
     * Пустой срок означает VIP без ограничения по времени.
     */
    public function setByAdmin(Product $product, bool $isVip, Carbon|string|null $until = null): Product
    {
        $vipUntil = $isVip && $until !== null && $until !== '' ? Carbon::parse($until) : null;

        $product->forceFill([
            'is_vip' => $isVip,
            'vip_until' => $isVip ? $vipUntil : null,
        ])->save();

        return $product->refresh();
    }

    /**
     * Снимает VIP с товаров, у которых истёк оплаченный срок.
     */
    public function expireDue(): int
    {
        $expired = 0;

        Product::query()
            ->where('is_vip', true)
            ->whereNotNull('vip_until')
            ->where('vip_until', '<=', now())
            ->with('seller.user')
            ->chunkById(100, function ($products) use (&$expired): void {
                foreach ($products as $product) {
                    $product->forceFill(['is_vip' => false, 'vip_until' => null])->save();
                    $expired++;

                    $product->seller?->user?->notify(new AccountNotification(
                        'vip_expired',
                        'VIP закончился',
                        'Товар «'.$product->name.'» больше не показывается как VIP. Продлить можно на странице товара.',
                        route('account.store.products.show', $product),
                        ['product_id' => $product->id],
                    ));
                }
            });

        return $expired;
    }

    /**
     * Предупреждает продавцов за сутки до окончания VIP (по одному уведомлению на срок).
     */
    public function notifyExpiringSoon(): int
    {
        $notified = 0;

        Product::query()
            ->where('is_vip', true)
            ->whereNotNull('vip_until')
            ->where('vip_until', '>', now())
            ->where('vip_until', '<=', now()->addDay())
            ->with('seller.user')
            ->chunkById(100, function ($products) use (&$notified): void {
                foreach ($products as $product) {
                    $owner = $product->seller?->user;

                    if ($owner === null) {
                        continue;
                    }

                    $until = $product->vip_until->format('Y-m-d H:i');
                    $alreadySent = $owner->notifications()
                        ->where('data->type', 'vip_expiring')
                        ->where('data->product_id', $product->id)
                        ->where('data->vip_until', $until)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $owner->notify(new AccountNotification(
                        'vip_expiring',
                        'VIP скоро закончится',
                        'Товар «'.$product->name.'»: VIP действует до '.$product->vip_until->format('d.m.Y H:i').'. Продлите заранее, чтобы не потерять место в выдаче.',
                        route('account.store.products.show', $product),
                        ['product_id' => $product->id, 'vip_until' => $until],
                    ));
                    $notified++;
                }
            });

        return $notified;
    }

    private function toCents(int|float|string $amount): int
    {
        return (int) round(((float) str_replace(',', '.', (string) $amount)) * 100);
    }
}
