<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Services\CustomerOrderListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_form_is_shown_only_when_ordering_is_available(): void
    {
        $product = $this->createOrderableProduct();

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertSee('Оформить заказ')
            ->assertSee(route('orders.store', $product), false);

        $product->seller->update(['can_order' => false]);

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertDontSee('Оформить заказ');
    }

    public function test_product_page_preserves_description_line_breaks(): void
    {
        $product = $this->createOrderableProduct();
        $product->update(['desc' => "Первая строка\n\n- Первый пункт\n- Второй пункт"]);

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertSee('white-space: pre-line', false)
            ->assertSee("- Первый пункт\n- Второй пункт");
    }

    public function test_unavailable_ordering_is_not_silently_hidden(): void
    {
        $product = $this->createOrderableProduct();
        $product->update(['can_order' => false]);

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertSee('Заказ через сайт для этого товара сейчас недоступен.');
    }

    public function test_guest_can_create_order_and_open_protected_result(): void
    {
        $product = $this->createOrderableProduct();
        $idempotencyKey = (string) Str::uuid();

        $response = $this->post(route('orders.store', $product), [
            'customer_name' => 'Иван',
            'customer_phone' => '+373 777 12 345',
            'quantity' => 2,
            'fulfillment_method' => 'pickup',
            'customer_comment' => 'Позвоните заранее',
            'consent' => '1',
            'idempotency_key' => $idempotencyKey,
        ]);

        $order = Order::query()->sole();

        $response->assertRedirect(route('orders.show', $order->public_id));
        $response->assertCookie(CustomerOrderListService::COOKIE_NAME);

        $this->get(route('orders.show', $order->public_id))
            ->assertOk()
            ->assertSee('Заказ принят')
            ->assertSee($order->number)
            ->assertSee('Мои заказы')
            ->assertSee('window.print()', false)
            ->assertSee('@media print', false)
            ->assertSee('noindex, nofollow', false);
    }

    public function test_guest_can_see_created_orders_list(): void
    {
        $product = $this->createOrderableProduct();

        $this->post(route('orders.store', $product), $this->validPayload())->assertRedirect();
        $order = Order::query()->sole();

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Мои заказы')
            ->assertSee($order->number)
            ->assertSee($order->product_name)
            ->assertSee($order->seller_name)
            ->assertSee(route('orders.show', $order->public_id), false)
            ->assertSee('Удалить из списка');
    }

    public function test_customer_can_remove_order_from_saved_list(): void
    {
        $product = $this->createOrderableProduct();

        $this->post(route('orders.store', $product), $this->validPayload())->assertRedirect();
        $order = Order::query()->sole();

        $this->delete(route('orders.destroy', $order->public_id))->assertRedirect(route('orders.index'));

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertSee('У вас пока нет сохранённых заказов')
            ->assertDontSee($order->number);
    }

    public function test_saved_orders_cookie_restores_orders_after_session_is_lost(): void
    {
        $product = $this->createOrderableProduct();

        $response = $this->post(route('orders.store', $product), $this->validPayload());
        $order = Order::query()->sole();
        $cookieValue = null;

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === CustomerOrderListService::COOKIE_NAME) {
                $cookieValue = $cookie->getValue();
                break;
            }
        }

        $this->assertNotNull($cookieValue);

        Session::flush();

        $this->withUnencryptedCookie(CustomerOrderListService::COOKIE_NAME, $cookieValue)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee(route('orders.show', $order->public_id), false);
    }

    public function test_order_result_is_not_available_without_session_token(): void
    {
        $product = $this->createOrderableProduct();

        $this->post(route('orders.store', $product), $this->validPayload());
        $order = Order::query()->sole();

        Session::flush();

        $this->get(route('orders.show', $order->public_id))
            ->assertNotFound();
    }

    public function test_public_order_validates_consent_and_delivery_address(): void
    {
        $product = $this->createOrderableProduct(deliveryEnabled: true);

        $this->post(route('orders.store', $product), array_merge($this->validPayload(), [
            'fulfillment_method' => 'delivery',
            'delivery_address' => '',
            'consent' => null,
        ]))
            ->assertSessionHasErrors(['delivery_address', 'consent']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_repeated_submission_creates_only_one_order(): void
    {
        $product = $this->createOrderableProduct();
        $payload = $this->validPayload();

        $this->post(route('orders.store', $product), $payload)->assertRedirect();
        $this->post(route('orders.store', $product), $payload)->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_status_history', 1);
    }

    private function createOrderableProduct(bool $deliveryEnabled = false): Product
    {
        $package = Package::factory()->withLimits(50, 10)->create([
            'has_order_system' => true,
        ]);
        $seller = Seller::factory()->create([
            'can_order' => true,
            'delivery_enabled' => $deliveryEnabled,
        ]);

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        return Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => Category::factory(),
            'can_order' => true,
            'delivery_enabled' => $deliveryEnabled,
        ]);
    }

    private function validPayload(): array
    {
        return [
            'customer_name' => 'Иван',
            'customer_phone' => '+373 777 12 345',
            'quantity' => 1,
            'fulfillment_method' => 'pickup',
            'customer_comment' => null,
            'consent' => '1',
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
