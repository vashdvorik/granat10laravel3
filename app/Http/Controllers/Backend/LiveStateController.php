<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ProductModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Лёгкая проверка «что изменилось» для страниц кабинета, чтобы продавцу не приходилось обновлять страницу вручную.
 *
 * Страница раз в несколько секунд спрашивает: какая сейчас подпись состояния товара и какие уведомления. Если подпись
 * отличается от той, с которой страница открыта, скрипт сам подтягивает свежие блоки (статус, кнопки, колокольчик).
 * Сервер здесь ничего не рендерит и не обращается к модерации: только считает подпись и два числа.
 * Вебсокеты не нужны (на обычном хостинге их нет), модерация и так идёт через очередь с задержкой в десятки секунд.
 */
class LiveStateController extends Controller
{
    /** Пока товар проверяется, статус может смениться в любую секунду. */
    private const WHILE_CHECKING = 8;

    /** Ждёт ручной проверки администратором: быстро не изменится. */
    private const WHILE_WAITING_FOR_ADMIN = 30;

    /** Окончательное состояние или страница без товара: проверяем изредка (администратор мог скрыть товар или магазин). */
    private const IDLE = 60;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payload = [
            'notifications' => $this->notificationsToken($user),
            'poll' => self::IDLE,
        ];

        $productId = (int) $request->query('product');

        if ($productId > 0) {
            $product = Product::query()->find($productId);

            // Чужой или удалённый товар не раскрываем: страница просто перестанет следить за товаром
            if ($product && $user->can('view', $product)) {
                $payload['product'] = [
                    'id' => $product->id,
                    'signature' => $product->cabinet_state_signature,
                ];
                $payload['poll'] = $this->interval($product->moderation_status);
            }
        }

        return response()->json($payload)->header('Cache-Control', 'no-store');
    }

    /** Меняется, когда пришло новое уведомление или часть прочитана: число непрочитанных и номер последнего. */
    private function notificationsToken(User $user): string
    {
        // Порядок тот же, что у колокольчика в шаблоне (created_at, затем id), иначе при равных датах метки расходились бы
        return $user->unreadNotifications()->count().':'.($user->notifications()->latest()->orderByDesc('id')->value('id') ?? '');
    }

    private function interval(?ProductModerationStatus $status): int
    {
        return match ($status) {
            ProductModerationStatus::Pending, ProductModerationStatus::Processing => self::WHILE_CHECKING,
            ProductModerationStatus::NeedsReview, ProductModerationStatus::Failed => self::WHILE_WAITING_FOR_ADMIN,
            default => self::IDLE,
        };
    }
}
