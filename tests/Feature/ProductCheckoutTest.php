<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Services\DesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Заказ оформляется на отдельной странице: на странице товара остаются «Позвонить продавцу» и либо кнопка
 * «Оформить заказ», либо предупреждение. Сама страница оформления понятна и объясняет, что будет дальше.
 */
class ProductCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function themes(): array
    {
        return ['legacy' => ['legacy'], 'new' => ['new'], 'joom' => ['joom']];
    }

    /** @param  array<string, mixed>  $productOverrides @param  array<string, mixed>  $sellerOverrides */
    private function orderableProduct(array $productOverrides = [], array $sellerOverrides = []): Product
    {
        $seller = Seller::factory()->create(array_merge([
            'can_order' => true, 'delivery_enabled' => false, 'phone' => '+373 777 12 345',
        ], $sellerOverrides));

        return Product::factory()->create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => Category::factory()->create()->id,
            'is_active' => true,
            'can_order' => true,
            'price' => 1500,
            'lowprice' => null,
            'desc' => 'Подробное описание товара',
        ], $productOverrides));
    }

    // ------------------------------------------------------------------ страница товара

    /** @dataProvider themes */
    public function test_product_page_shows_call_and_checkout_buttons_without_the_form(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);
        $product = $this->orderableProduct();

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertSee('href="'.route('orders.create', ['alias' => $product->alias]).'"', false)
            ->assertSee('Оформить заказ')
            ->assertSee('href="tel:+37377712345"', false)
            ->assertDontSee('id="order-form"', false)
            ->assertDontSee('data-order-form', false)
            ->assertDontSee('Отправить заказ');
    }

    /** @dataProvider themes */
    public function test_when_ordering_is_unavailable_the_warning_replaces_the_checkout_button(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);
        $product = $this->orderableProduct(['can_order' => false], ['can_order' => true]);

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertDontSee('Оформить заказ')
            ->assertDontSee(route('orders.create', ['alias' => $product->alias]), false)
            ->assertSee('Позвонить продавцу')
            ->assertSee('href="tel:+37377712345"', false)
            ->assertSee('недоступен');
    }

    public function test_mobile_bar_order_button_leads_to_the_checkout_page(): void
    {
        app(DesignManager::class)->setActive('legacy');
        $product = $this->orderableProduct();

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertSee('href="'.route('orders.create', ['alias' => $product->alias]).'" class="product-mobile-actions__order"', false);
    }

    // ------------------------------------------------------------------ страница оформления

    /** @dataProvider themes */
    public function test_checkout_page_shows_product_form_total_and_next_steps(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);
        $product = $this->orderableProduct(['price' => 2000, 'lowprice' => 1500, 'name' => 'Чайник для оформления']);

        $html = $this->get(route('orders.create', ['alias' => $product->alias]))
            ->assertOk()
            ->assertSee('Оформление заказа')
            ->assertSee('Чайник для оформления')
            ->assertSee('Вернуться к товару')
            ->assertSee('action="'.route('orders.store', $product).'"', false)
            ->assertSee('name="idempotency_key"', false)
            ->assertSee('data-unit-price="1500"', false) // сумма считается по цене со скидкой
            ->assertSee('data-order-total', false)
            ->assertSee('Что будет дальше')
            ->assertSee('Продавец звонит и подтверждает наличие и цену')
            ->assertSee('платите продавцу напрямую')
            ->assertSee('Хотите сначала уточнить?')
            ->assertSee('href="tel:+37377712345"', false)
            ->assertSee('data-qty-step="1"', false)
            ->getContent();

        // страница технической выдачи: общий SeoService закрывает orders.* от индексации
        $this->assertSame(1, substr_count($html, 'name="robots"'));
        $this->assertStringContainsString('<meta name="robots" content="noindex, follow">', $html);
    }

    public function test_checkout_page_redirects_back_to_the_product_when_ordering_is_unavailable(): void
    {
        $closed = $this->orderableProduct(['can_order' => false]);

        $this->get(route('orders.create', ['alias' => $closed->alias]))
            ->assertRedirect(route('product.show', ['alias' => $closed->alias]));

        $shopClosed = $this->orderableProduct([], ['can_order' => false]);

        $this->get(route('orders.create', ['alias' => $shopClosed->alias]))
            ->assertRedirect(route('product.show', ['alias' => $shopClosed->alias]));
    }

    public function test_checkout_page_is_not_found_for_hidden_or_unknown_products(): void
    {
        $hidden = $this->orderableProduct(['is_active' => false]);

        $this->get(route('orders.create', ['alias' => $hidden->alias]))->assertNotFound();
        $this->get(route('orders.create', ['alias' => 'net-takogo-tovara']))->assertNotFound();
    }

    public function test_delivery_choice_and_address_appear_only_when_delivery_is_available(): void
    {
        $withDelivery = $this->orderableProduct(['delivery_enabled' => true], [
            'delivery_enabled' => true, 'delivery_terms' => 'Доставка по городу.',
        ]);

        $this->get(route('orders.create', ['alias' => $withDelivery->alias]))
            ->assertOk()
            ->assertSee('value="pickup"', false)
            ->assertSee('value="delivery"', false)
            ->assertSee('Условия доставки')
            ->assertSee('Доставка по городу.')
            ->assertSee('name="delivery_address"', false);

        $pickupOnly = $this->orderableProduct();

        $this->get(route('orders.create', ['alias' => $pickupOnly->alias]))
            ->assertOk()
            ->assertDontSee('value="delivery"', false)
            ->assertDontSee('name="delivery_address"', false)
            ->assertSee('<input type="hidden" name="fulfillment_method" value="pickup">', false)
            ->assertSee('Самовывоз у продавца');
    }

    public function test_validation_errors_return_to_the_checkout_page_and_are_shown_in_the_form(): void
    {
        $product = $this->orderableProduct();
        $checkout = route('orders.create', ['alias' => $product->alias]);

        $this->from($checkout)
            ->post(route('orders.store', $product), [
                'customer_name' => '',
                'customer_phone' => '',
                'quantity' => 1,
                'fulfillment_method' => 'pickup',
                'idempotency_key' => (string) Str::uuid(),
            ])
            ->assertRedirect($checkout)
            ->assertSessionHasErrors(['customer_name', 'customer_phone', 'consent']);

        $this->get($checkout)
            ->assertOk()
            ->assertSee('Проверьте, пожалуйста, подсвеченные поля ниже.');
    }

    // ------------------------------------------------------------------ после заказа

    /** @dataProvider themes */
    public function test_confirmation_page_shows_order_details_seller_contacts_and_next_steps(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);
        $product = $this->orderableProduct(['delivery_enabled' => true], ['delivery_enabled' => true]);

        $this->post(route('orders.store', $product), [
            'customer_name' => 'Иван',
            'customer_phone' => '+373 777 00 111',
            'quantity' => 2,
            'fulfillment_method' => 'delivery',
            'delivery_address' => 'Тирасполь, ул. Тестовая, 1',
            'consent' => '1',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $order = Order::query()->sole();

        $this->get(route('orders.show', $order->public_id))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('3 000 ₽')
            ->assertSee('Доставка продавцом: Тирасполь, ул. Тестовая, 1')
            ->assertSee('Что будет дальше')
            ->assertSee('Продавец позвонит вам по номеру')
            ->assertSee('Позвонить продавцу: +373 777 12 345')
            ->assertSee('href="tel:+37377712345"', false)
            ->assertSee('Мои заказы');
    }
}
