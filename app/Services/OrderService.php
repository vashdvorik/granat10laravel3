<?php

namespace App\Services;

use App\Data\CreatedOrder;
use App\Data\OrderCreationData;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Exceptions\OrderUnavailableException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly OrderAvailabilityService $availability,
        private readonly OrderTokenService $tokens,
    ) {}

    public function create(Product $product, OrderCreationData $data): CreatedOrder
    {
        $idempotencyHash = $this->tokens->idempotencyHash($data->idempotencyKey);
        $accessToken = $this->tokens->accessToken($data->idempotencyKey);

        $existingOrder = Order::query()
            ->where('idempotency_key_hash', $idempotencyHash)
            ->first();

        if ($existingOrder !== null) {
            return new CreatedOrder($existingOrder, $accessToken, true);
        }

        return DB::transaction(function () use ($product, $data, $idempotencyHash, $accessToken): CreatedOrder {
            $existingOrder = Order::query()
                ->where('idempotency_key_hash', $idempotencyHash)
                ->lockForUpdate()
                ->first();

            if ($existingOrder !== null) {
                return new CreatedOrder($existingOrder, $accessToken, true);
            }

            $lockedProduct = Product::query()
                ->with(['seller.settlement'])
                ->lockForUpdate()
                ->findOrFail($product->id);
            $availability = $this->availability->check($lockedProduct);

            if (! $availability->allowed) {
                throw new OrderUnavailableException($availability->reason ?? 'Заказ недоступен.');
            }

            if ($data->quantity < 1 || $data->quantity > 99) {
                throw new OrderUnavailableException('Количество должно быть от 1 до 99.');
            }

            if (! in_array($data->fulfillmentMethod, ['pickup', 'delivery'], true)) {
                throw new OrderUnavailableException('Неизвестный способ получения.');
            }

            if ($data->fulfillmentMethod === 'delivery') {
                if (! $availability->deliveryAllowed) {
                    throw new OrderUnavailableException('Доставка для товара недоступна.');
                }

                if (blank($data->deliveryAddress)) {
                    throw new OrderUnavailableException('Для доставки требуется адрес.');
                }
            }

            $seller = $lockedProduct->seller;
            $unitPrice = $lockedProduct->lowprice ?? $lockedProduct->price;
            $now = now();

            $order = new Order;
            $order->forceFill([
                'number' => 'ORD-'.Str::upper(Str::ulid()->toBase32()),
                'public_id' => (string) Str::ulid(),
                'access_token_hash' => $this->tokens->accessTokenHash($accessToken),
                'idempotency_key_hash' => $idempotencyHash,
                'seller_id' => $seller->id,
                'product_id' => $lockedProduct->id,
                'status' => OrderStatus::New,
                'customer_name' => trim($data->customerName),
                'customer_phone' => $this->tokens->normalizePhone($data->customerPhone),
                'customer_phone_hash' => $this->tokens->phoneHash($data->customerPhone),
                'quantity' => $data->quantity,
                'fulfillment_method' => $data->fulfillmentMethod,
                'delivery_address' => $data->fulfillmentMethod === 'delivery'
                    ? trim((string) $data->deliveryAddress)
                    : null,
                'customer_comment' => filled($data->customerComment)
                    ? trim((string) $data->customerComment)
                    : null,
                'product_name' => $lockedProduct->name,
                'seller_name' => $seller->name,
                'seller_phone' => $seller->phone,
                'original_unit_price' => $lockedProduct->price,
                'unit_price' => $unitPrice,
                'delivery_terms' => $seller->delivery_terms,
                'settlement_name' => $seller->settlement?->name,
                'consent_accepted_at' => $now,
                'consent_version' => $data->consentVersion,
                'source_ip_hash' => $this->tokens->sourceIpHash($data->sourceIp),
                'status_changed_at' => $now,
            ])->save();

            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => OrderStatus::New,
                'changed_by_user_id' => null,
                'comment' => 'Заказ создан покупателем.',
            ]);

            OrderCreated::dispatch($order->id);

            return new CreatedOrder($order->fresh(), $accessToken, false);
        }, 3);
    }
}
