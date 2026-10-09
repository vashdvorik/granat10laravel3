<?php

namespace App\Services;

use App\Data\VipState;
use App\Enums\BalanceTransactionType;
use App\Models\Product;
use App\Models\User;
use App\Models\VipPurchase;
use App\Notifications\AccountNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductVipService
{
    /** Причины, по которым VIP купить нельзя, простым языком (коды — из ProductVisibilityResolver). */
    private const BLOCKERS = [
        'moderation_not_approved' => 'Товар ещё не прошёл проверку. VIP можно подключить после одобрения.',
        'moderation_outdated' => 'Вы изменили товар после проверки. Дождитесь повторной проверки, затем подключите VIP.',
        'not_enabled_by_seller' => 'Товар снят с публикации. Опубликуйте его, чтобы подключить VIP.',
        'seller_hidden' => 'Магазин скрыт администратором. VIP можно подключить, когда магазин вернётся на сайт.',
    ];

    public function __construct(
        private readonly VipPricing $pricing,
        private readonly ProductVisibilityResolver $visibilityResolver,
        private readonly BalanceLedger $ledger,
    ) {}

    /**
     * Полное описание VIP товара для интерфейса: статус, срок, варианты покупки с ценами и итоговой датой,
     * хватает ли денег, можно ли отключить и сколько вернётся.
     */
    public function state(Product $product, ?User $user = null): VipState
    {
        $user ??= $product->seller?->user;
        $balance = $user ? $this->toCents($user->getRawOriginal('balance') ?? '0') : 0;
        $active = $product->isVipActive();
        $permanent = $active && $product->vip_until === null;
        $until = $active ? $product->vip_until : null;
        $secondsLeft = $until ? max(0, $until->getTimestamp() - now()->getTimestamp()) : null;
        $daysLeft = $secondsLeft === null ? null : (int) ceil($secondsLeft / 86400);

        $snapshot = $this->visibilityResolver->snapshot($product);
        $blockers = [];

        if (! $snapshot->publicVisible) {
            $reasons = $snapshot->buyerBlockingReasons;
            $moderationBlocks = in_array('moderation_not_approved', $reasons, true) || in_array('moderation_outdated', $reasons, true);

            foreach (self::BLOCKERS as $code => $message) {
                // Пока товар не прошёл проверку, «снят с публикации» продавцу ничего не говорит: он его не снимал
                if ($code === 'not_enabled_by_seller' && $moderationBlocks) {
                    continue;
                }

                if (in_array($code, $reasons, true)) {
                    $blockers[] = ['code' => $code, 'message' => $message];
                }
            }
        }

        $warnings = [];

        if ($snapshot->publicVisible && ! $snapshot->hasImages) {
            $warnings[] = 'У товара нет фото. VIP поднимет его выше, но без фото покупатели откроют объявление реже.';
        }

        if ($active && ! $snapshot->publicVisible) {
            $warnings[] = 'Пока товар не опубликован, VIP не работает, но срок продолжает идти.';
        }

        $options = [];

        if (! $permanent) {
            $startsFrom = $until ?? now();

            foreach ($this->pricing->durations() as $days => $label) {
                $price = $this->pricing->priceInCents((int) $days);
                $options[] = [
                    'days' => (int) $days,
                    'label' => $label,
                    'price_cents' => $price,
                    'per_day_cents' => $this->pricing->pricePerDayInCents(),
                    'new_until' => $startsFrom->copy()->addDays((int) $days),
                    'affordable' => $balance >= $price,
                    'shortfall_cents' => max(0, $price - $balance),
                ];
            }
        }

        [$refund, $canCancel, $cancelReason] = $this->cancellation($product);

        return new VipState(
            status: $permanent ? VipState::PERMANENT : ($active ? VipState::ACTIVE : VipState::INACTIVE),
            until: $until,
            daysLeft: $daysLeft,
            eligible: $snapshot->publicVisible,
            blockers: $blockers,
            warnings: $warnings,
            balanceCents: $balance,
            pricePerDayCents: $this->pricing->pricePerDayInCents(),
            options: $options,
            canCancel: $canCancel,
            refundCents: $refund,
            cancelBlockedReason: $cancelReason,
            expiresSoon: $active && $daysLeft !== null && $daysLeft <= 3,
        );
    }

    /**
     * Покупка (или продление) VIP для товара за деньги с баланса продавца.
     * Продление добавляется к текущему сроку. Операция пишется в журнал баланса и в историю покупок.
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
            $vipUntil = $startsFrom->copy()->addDays($days);
            $newBalanceInCents = $balanceInCents - $priceInCents;

            $lockedUser->forceFill(['balance' => $this->pricing->format($newBalanceInCents)])->save();
            $lockedProduct->forceFill([
                'is_vip' => true,
                'vip_until' => $vipUntil,
            ])->save();

            $purchase = VipPurchase::query()->create([
                'product_id' => $lockedProduct->getKey(),
                'user_id' => $lockedUser->getKey(),
                'days' => $days,
                'price_cents' => $priceInCents,
                'starts_at' => $startsFrom,
                'ends_at' => $vipUntil,
                'status' => VipPurchase::STATUS_ACTIVE,
            ]);

            $this->ledger->record(
                $lockedUser,
                BalanceTransactionType::VipPurchase,
                -$priceInCents,
                $newBalanceInCents,
                'VIP для товара «'.$lockedProduct->name.'» на '.$days.' дн. (до '.$vipUntil->format('d.m.Y').')',
                $purchase,
                ['product_id' => $lockedProduct->getKey(), 'days' => $days],
            );

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
     * Досрочное отключение VIP, купленного за баланс. Возвращает деньги за неиспользованные ПОЛНЫЕ дни
     * по той цене, по которой платили. Возвращает сумму возврата в копейках.
     */
    public function cancel(User $user, Product $product): int
    {
        return DB::transaction(function () use ($user, $product): int {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $lockedProduct->loadMissing('seller');

            if ($lockedProduct->seller === null || (int) $lockedProduct->seller->user_id !== (int) $lockedUser->getKey()) {
                throw new RuntimeException('Этот товар вам не принадлежит.');
            }

            if (! $lockedProduct->isVipActive()) {
                throw new RuntimeException('У этого товара нет действующего VIP.');
            }

            [, $canCancel, $reason] = $this->cancellation($lockedProduct);

            if (! $canCancel) {
                throw new RuntimeException($reason ?? 'Этот VIP нельзя отключить досрочно.');
            }

            $purchases = $this->openPurchases($lockedProduct, lock: true);
            $refund = 0;

            foreach ($purchases as $purchase) {
                $purchaseRefund = $this->purchaseRefund($purchase);
                $refund += $purchaseRefund;

                $purchase->forceFill([
                    'status' => VipPurchase::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'refunded_cents' => $purchaseRefund,
                    'ends_at' => $purchase->starts_at->isPast() ? now() : $purchase->ends_at,
                ])->save();
            }

            $lockedProduct->forceFill(['is_vip' => false, 'vip_until' => null])->save();

            $balanceBefore = $this->toCents($lockedUser->getRawOriginal('balance') ?? '0');
            $balanceAfter = $balanceBefore + $refund;

            if ($refund > 0) {
                $lockedUser->forceFill(['balance' => $this->pricing->format($balanceAfter)])->save();

                $this->ledger->record(
                    $lockedUser,
                    BalanceTransactionType::VipRefund,
                    $refund,
                    $balanceAfter,
                    'Возврат за неиспользованные дни VIP, товар «'.$lockedProduct->name.'»',
                    $purchases->last(),
                    ['product_id' => $lockedProduct->getKey()],
                );
            }

            activity('products')
                ->causedBy($lockedUser)
                ->performedOn($lockedProduct)
                ->withProperties([
                    'action' => 'vip_cancel',
                    'refund' => $this->pricing->format($refund),
                    'balance_after' => $this->pricing->format($balanceAfter),
                ])
                ->log('VIP отключён досрочно для товара "'.$lockedProduct->name.'"');

            return $refund;
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
                        'Товар «'.$product->name.'» больше не показывается как VIP. Продлить можно на странице VIP-размещения.',
                        route('account.vip.index'),
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
                        route('account.vip.index'),
                        ['product_id' => $product->id, 'vip_until' => $until],
                    ));
                    $notified++;
                }
            });

        return $notified;
    }

    /**
     * @return array{0: int, 1: bool, 2: ?string} сумма возврата (коп.), можно ли отключить, причина запрета
     */
    private function cancellation(Product $product): array
    {
        if (! $product->isVipActive()) {
            return [0, false, null];
        }

        if ($product->vip_until === null) {
            return [0, false, 'Бессрочный VIP выдан администратором — снять его может только администратор.'];
        }

        $purchases = $this->openPurchases($product);

        if ($purchases->isEmpty()) {
            return [0, false, 'Этот VIP выдан администратором или куплен до обновления сервиса. Отключить его досрочно нельзя, он закончится сам.'];
        }

        if ($product->vip_until->gt($purchases->max('ends_at')->copy()->addMinute())) {
            return [0, false, 'Часть срока выдана администратором, поэтому отключить VIP досрочно нельзя.'];
        }

        return [$purchases->sum(fn (VipPurchase $purchase): int => $this->purchaseRefund($purchase)), true, null];
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, VipPurchase> покупки, которые ещё не закончились и не отменены */
    private function openPurchases(Product $product, bool $lock = false)
    {
        $query = VipPurchase::query()
            ->where('product_id', $product->getKey())
            ->where('status', VipPurchase::STATUS_ACTIVE)
            ->where('ends_at', '>', now())
            ->orderBy('starts_at');

        return ($lock ? $query->lockForUpdate() : $query)->get();
    }

    /** Возврат по одной покупке: неиспользованные полные дни × цена дня, по которой платили. */
    private function purchaseRefund(VipPurchase $purchase): int
    {
        if ($purchase->starts_at->isFuture()) {
            $unusedDays = $purchase->days;
        } else {
            $secondsLeft = max(0, $purchase->ends_at->getTimestamp() - now()->getTimestamp());
            $unusedDays = min($purchase->days, intdiv($secondsLeft, 86400));
        }

        return intdiv($purchase->price_cents * $unusedDays, max(1, $purchase->days));
    }

    private function toCents(int|float|string $amount): int
    {
        return (int) round(((float) str_replace(',', '.', (string) $amount)) * 100);
    }
}
