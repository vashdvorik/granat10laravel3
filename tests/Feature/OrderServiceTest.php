<?php

namespace Tests\Feature;

use App\Data\OrderCreationData;
use App\Exceptions\OrderUnavailableException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use App\Services\OrderAvailabilityService;
use App\Services\OrderService;
use App\Services\OrderTokenService;
use App\Services\RegionalContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_product_can_be_ordered_with_server_snapshot(): void
    {
        [$product, $seller] = $this->orderableProduct([
            'price' => 500,
            'lowprice' => 450,
        ]);

        $result = app(OrderService::class)->create($product, $this->data());

        $this->assertFalse($result->wasAlreadyCreated);
        $this->assertSame($product->id, $result->order->product_id);
        $this->assertSame($seller->id, $result->order->seller_id);
        $this->assertSame('500.00', $result->order->original_unit_price);
        $this->assertSame('450.00', $result->order->unit_price);
        $this->assertSame($product->name, $result->order->product_name);
        $this->assertSame($seller->name, $result->order->seller_name);
        $this->assertCount(1, $result->order->statusHistory);
        $this->assertNotSame($result->accessToken, $result->order->access_token_hash);
    }

    public function test_repeated_idempotency_key_returns_same_order_and_token(): void
    {
        [$product] = $this->orderableProduct();
        $data = $this->data(idempotencyKey: 'same-form-submission-key');

        $first = app(OrderService::class)->create($product, $data);
        $second = app(OrderService::class)->create($product, $data);

        $this->assertSame($first->order->id, $second->order->id);
        $this->assertSame($first->accessToken, $second->accessToken);
        $this->assertTrue($second->wasAlreadyCreated);
        $this->assertSame(1, Order::query()->count());
    }

    public function test_order_is_blocked_when_store_disallows_orders(): void
    {
        [$product, $seller] = $this->orderableProduct();
        $seller->forceFill(['can_order' => false])->save();

        $this->expectException(OrderUnavailableException::class);

        app(OrderService::class)->create($product, $this->data());
    }

    public function test_inactive_product_cannot_be_ordered(): void
    {
        [$product] = $this->orderableProduct();
        $product->forceFill(['is_active' => false])->save();

        $this->expectException(OrderUnavailableException::class);
        $this->expectExceptionMessage('Товар недоступен для заказа.');

        app(OrderService::class)->create($product, $this->data());
    }

    public function test_delivery_requires_store_product_and_address_permission(): void
    {
        [$product, $seller] = $this->orderableProduct();
        $seller->forceFill(['delivery_enabled' => true])->save();
        $product->forceFill(['delivery_enabled' => true])->save();

        $this->expectException(OrderUnavailableException::class);
        $this->expectExceptionMessage('адрес');

        app(OrderService::class)->create(
            $product,
            $this->data(fulfillmentMethod: 'delivery', deliveryAddress: null)
        );
    }

    public function test_delivery_order_saves_address_when_enabled(): void
    {
        [$product, $seller] = $this->orderableProduct();
        $seller->forceFill([
            'delivery_enabled' => true,
            'delivery_terms' => 'Доставка согласовывается.',
        ])->save();
        $product->forceFill(['delivery_enabled' => true])->save();

        $result = app(OrderService::class)->create(
            $product,
            $this->data(
                fulfillmentMethod: 'delivery',
                deliveryAddress: 'Тирасполь, улица Ленина, 1'
            )
        );

        $this->assertTrue($result->order->isDelivery());
        $this->assertSame('Тирасполь, улица Ленина, 1', $result->order->delivery_address);
        $this->assertSame('Доставка согласовывается.', $result->order->delivery_terms);
    }

    public function test_product_from_other_selected_settlement_cannot_be_ordered(): void
    {
        [$product] = $this->orderableProduct();
        $otherSettlement = Settlement::factory()->create();
        app(RegionalContext::class)->setSettlement($otherSettlement);

        $availability = app(OrderAvailabilityService::class)->check($product);

        $this->assertFalse($availability->allowed);
        $this->assertSame('Товар относится к другому городу.', $availability->reason);
    }

    public function test_quantity_must_be_between_one_and_ninety_nine(): void
    {
        [$product] = $this->orderableProduct();

        $this->expectException(OrderUnavailableException::class);

        app(OrderService::class)->create($product, $this->data(quantity: 100));
    }

    public function test_token_and_phone_hashes_are_stable_without_exposing_values(): void
    {
        $tokens = app(OrderTokenService::class);

        $this->assertSame(
            $tokens->accessToken('key'),
            $tokens->accessToken('key')
        );
        $this->assertSame(
            $tokens->phoneHash('+373 (777) 77-777'),
            $tokens->phoneHash('+37377777777')
        );
        $this->assertNotSame('+37377777777', $tokens->phoneHash('+37377777777'));
    }

    private function orderableProduct(array $productAttributes = []): array
    {
        $seller = Seller::factory()->create(['can_order' => true]);


        $product = Product::factory()->create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => Category::factory(),
            'can_order' => true,
        ], $productAttributes));

        return [$product, $seller];
    }

    private function data(
        int $quantity = 1,
        string $fulfillmentMethod = 'pickup',
        ?string $deliveryAddress = null,
        string $idempotencyKey = 'form-key-1234567890'
    ): OrderCreationData {
        return new OrderCreationData(
            customerName: 'Иван',
            customerPhone: '+373 (777) 77-777',
            quantity: $quantity,
            fulfillmentMethod: $fulfillmentMethod,
            deliveryAddress: $deliveryAddress,
            customerComment: 'Позвонить заранее',
            consentVersion: '1.0',
            idempotencyKey: $idempotencyKey,
            sourceIp: '127.0.0.1',
        );
    }
}
