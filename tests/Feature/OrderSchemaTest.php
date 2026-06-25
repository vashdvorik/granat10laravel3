<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_tables_and_settings_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('orders', [
            'number',
            'public_id',
            'access_token_hash',
            'idempotency_key_hash',
            'customer_phone_hash',
            'original_unit_price',
            'unit_price',
            'anonymized_at',
        ]));
        $this->assertTrue(Schema::hasTable('order_status_history'));
        $this->assertTrue(Schema::hasColumns('sellers', [
            'delivery_enabled',
            'delivery_terms',
            'telegram_notifications_enabled',
        ]));
        $this->assertTrue(Schema::hasColumns('products', ['can_order', 'delivery_enabled']));
        $this->assertFalse(Schema::hasColumn('orders', 'product_variant_id'));
    }

    public function test_personal_order_fields_are_encrypted_at_rest(): void
    {
        $order = Order::factory()->create([
            'customer_name' => 'Иван Тестов',
            'customer_phone' => '+37377777777',
            'delivery_address' => 'Тирасполь, улица Тестовая, 1',
            'customer_comment' => 'Позвонить заранее',
        ]);

        $raw = DB::table('orders')->where('id', $order->id)->first();

        $this->assertNotSame('Иван Тестов', $raw->customer_name);
        $this->assertNotSame('+37377777777', $raw->customer_phone);
        $this->assertNotSame('Тирасполь, улица Тестовая, 1', $raw->delivery_address);
        $this->assertSame('Иван Тестов', $order->fresh()->customer_name);
        $this->assertSame('+37377777777', $order->fresh()->customer_phone);
    }

    public function test_order_relations_and_status_casts_work(): void
    {
        $seller = Seller::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);
        $order = Order::factory()->create([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'status' => OrderStatus::New,
        ]);
        $history = OrderStatusHistory::factory()->create(['order_id' => $order->id]);

        $this->assertTrue($seller->orders->contains($order));
        $this->assertTrue($product->orders->contains($order));
        $this->assertTrue($order->statusHistory->contains($history));
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertFalse($order->isFinal());
    }

    public function test_order_status_transitions_are_fixed(): void
    {
        $this->assertTrue(OrderStatus::New->canTransitionTo(OrderStatus::Accepted));
        $this->assertTrue(OrderStatus::Processing->canTransitionTo(OrderStatus::Completed));
        $this->assertFalse(OrderStatus::Completed->canTransitionTo(OrderStatus::Processing));
        $this->assertTrue(OrderStatus::Cancelled->isFinal());
    }

    public function test_order_rejects_mass_assignment_of_service_fields(): void
    {
        $order = new Order;

        $this->expectException(MassAssignmentException::class);

        $order->fill([
            'status' => OrderStatus::Completed,
            'unit_price' => 1,
            'seller_id' => 999,
        ]);
    }
}
