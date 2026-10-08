<?php

namespace Tests\Feature;

use App\Application\Products\CreateProduct;
use App\Application\Products\UpdateProduct;
use App\Enums\ProductModerationStatus;
use App\Enums\ProductSaveAction;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApplicationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_action_creates_draft_for_explicit_seller_and_actor(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();

        $result = app(CreateProduct::class)->execute(
            $seller,
            $seller->user,
            [
                'name' => 'Товар через action',
                'price' => 500,
                'category_id' => $category->id,
                'save_action' => ProductSaveAction::Draft->value,
                'is_vip' => false,
            ]
        );
        $product = $result->product;

        $this->assertSame($seller->id, $product->seller_id);
        $this->assertSame(ProductModerationStatus::Draft, $product->moderation_status);
        $this->assertStringContainsString('Черновик сохранён', $result->userMessage);
        $this->assertStringContainsString('Покупатели его не видят', $result->userMessage);
    }

    public function test_update_action_updates_existing_product(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Старое название',
        ]);

        $result = app(UpdateProduct::class)->execute(
            $product,
            $seller->user,
            [
                'name' => 'Новое название',
                'price' => 700,
                'category_id' => $category->id,
                'save_action' => ProductSaveAction::Draft->value,
                'is_vip' => false,
            ]
        );

        $this->assertSame('Новое название', $result->product->name);
        // опубликованный товар, у которого поменяли название: честно говорим, что он скрыт до новой проверки
        $this->assertStringContainsString('скрыт из каталога', $result->userMessage);
        $this->assertStringContainsString('отправьте его на проверку', $result->userMessage);
    }
}
