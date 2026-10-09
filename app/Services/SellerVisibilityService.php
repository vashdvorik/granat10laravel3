<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * «Скрыть продавца»: администратор снимает магазин и все его товары с публичного сайта и пишет причину.
 * Данные продавца и товаров не удаляются; продавец входит в кабинет и видит причину красным блоком.
 * Товары скрываются условием в `Product::visible()` и `Seller::publiclyVisible()`, поэтому здесь нет ручного перебора.
 */
class SellerVisibilityService
{
    public const MAX_REASON_LENGTH = 1000;

    /** Скрывает магазин или, если он уже скрыт, обновляет причину. */
    public function hide(User $actor, Seller $seller, string $reason): Seller
    {
        Gate::forUser($actor)->authorize('manageVisibility', $seller);

        $reason = $this->validReason($reason);

        $locked = DB::transaction(function () use ($actor, $seller, $reason): Seller {
            $locked = Seller::query()->lockForUpdate()->findOrFail($seller->getKey());
            $wasHidden = $locked->isHidden();

            $locked->forceFill([
                'hidden_at' => $wasHidden ? $locked->hidden_at : now(),
                'hidden_reason' => $reason,
                'hidden_by_user_id' => $actor->getKey(),
            ])->save();

            activity('sellers')
                ->causedBy($actor)
                ->performedOn($locked)
                ->withProperties([
                    'visibility' => 'hidden',
                    'reason' => $reason,
                    'seller_id' => $locked->getKey(),
                    'seller_name' => $locked->name,
                    'owner_id' => $locked->user_id,
                ])
                ->log($wasHidden ? 'Причина скрытия магазина изменена' : 'Магазин скрыт администратором');

            return $locked;
        });

        $this->afterChange($locked, hidden: true);
        $seller->refresh();

        return $seller;
    }

    /** Возвращает магазин на сайт и очищает причину. */
    public function show(User $actor, Seller $seller): Seller
    {
        Gate::forUser($actor)->authorize('manageVisibility', $seller);

        $locked = DB::transaction(function () use ($actor, $seller): Seller {
            $locked = Seller::query()->lockForUpdate()->findOrFail($seller->getKey());

            if (! $locked->isHidden()) {
                return $locked;
            }

            $previousReason = $locked->hidden_reason;

            $locked->forceFill([
                'hidden_at' => null,
                'hidden_reason' => null,
                'hidden_by_user_id' => null,
            ])->save();

            activity('sellers')
                ->causedBy($actor)
                ->performedOn($locked)
                ->withProperties([
                    'visibility' => 'shown',
                    'previous_reason' => $previousReason,
                    'seller_id' => $locked->getKey(),
                    'seller_name' => $locked->name,
                    'owner_id' => $locked->user_id,
                ])
                ->log('Магазин снова показан покупателям');

            return $locked;
        });

        $this->afterChange($locked, hidden: false);
        $seller->refresh();

        return $seller;
    }

    private function validReason(string $reason): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Напишите причину: её увидит продавец в кабинете.',
            ]);
        }

        if (mb_strlen($reason) > self::MAX_REASON_LENGTH) {
            throw ValidationException::withMessages([
                'reason' => 'Причина слишком длинная: не больше '.self::MAX_REASON_LENGTH.' символов.',
            ]);
        }

        return $reason;
    }

    /** Кеши каталога сбрасывает SellerObserver; здесь — карта сайта и поисковый индекс. */
    private function afterChange(Seller $seller, bool $hidden): void
    {
        Cache::forget('seo:sitemap:entries');

        // Поисковый индекс — удобство: каталог всё равно отсекает скрытые товары через Product::visible()
        rescue(function () use ($seller, $hidden): void {
            $products = Product::query()->where('seller_id', $seller->getKey());

            $hidden ? $products->unsearchable() : $products->searchable();
        });
    }
}
