<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $phone = '+373'.fake()->numerify('########');

        return [
            'number' => 'ORD-'.fake()->unique()->numerify('########'),
            'public_id' => (string) Str::ulid(),
            'access_token_hash' => hash('sha256', Str::random(64)),
            'idempotency_key_hash' => hash('sha256', (string) Str::uuid()),
            'seller_id' => Seller::factory(),
            'product_id' => Product::factory(),
            'status' => OrderStatus::New,
            'customer_name' => fake()->name(),
            'customer_phone' => $phone,
            'customer_phone_hash' => hash_hmac('sha256', $phone, config('app.key')),
            'quantity' => 1,
            'fulfillment_method' => 'pickup',
            'delivery_address' => null,
            'customer_comment' => null,
            'product_name' => fake()->words(3, true),
            'seller_name' => fake()->company(),
            'seller_phone' => fake()->phoneNumber(),
            'original_unit_price' => 100,
            'unit_price' => 100,
            'delivery_terms' => null,
            'settlement_name' => fake()->city(),
            'consent_accepted_at' => now(),
            'consent_version' => '1.0',
            'source_ip_hash' => hash('sha256', fake()->ipv4()),
            'status_changed_at' => now(),
        ];
    }
}
