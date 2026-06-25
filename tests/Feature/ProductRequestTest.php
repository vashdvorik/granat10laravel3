<?php

namespace Tests\Feature;

use App\Enums\ProductSaveAction;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_product_request_accepts_valid_payload(): void
    {
        $category = Category::factory()->create();
        $request = new StoreProductRequest;

        $validator = Validator::make([
            'name' => 'Valid product',
            'price' => 100,
            'lowprice' => 90,
            'category_id' => $category->id,
            'desc' => 'Description',
            'save_action' => ProductSaveAction::Draft->value,
            'is_vip' => '0',
            'images' => [
                UploadedFile::fake()->image('product.jpg'),
            ],
        ], $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    public function test_product_request_accepts_save_action_and_maps_legacy_is_active(): void
    {
        $request = StoreProductRequest::create('/', 'POST', [
            'name' => 'Товар',
            'price' => 100,
            'category_id' => 1,
            'save_action' => ProductSaveAction::Submit->value,
        ]);

        $this->assertSame(ProductSaveAction::Submit->value, $request->input('save_action'));

        $legacyRequest = StoreProductRequest::create('/', 'POST', [
            'is_active' => '0',
        ]);
        $method = new \ReflectionMethod($legacyRequest, 'prepareForValidation');
        $method->invoke($legacyRequest);

        $this->assertSame(ProductSaveAction::Draft->value, $legacyRequest->input('save_action'));
    }

    public function test_store_product_request_rejects_missing_name(): void
    {
        $category = Category::factory()->create();
        $request = new StoreProductRequest;

        $validator = Validator::make([
            'price' => 100,
            'category_id' => $category->id,
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_store_product_request_requires_discount_price_below_price(): void
    {
        $category = Category::factory()->create();
        $request = new StoreProductRequest;

        $validator = Validator::make([
            'name' => 'Invalid discount',
            'price' => 100,
            'lowprice' => 100,
            'category_id' => $category->id,
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('lowprice'));
    }

    public function test_update_product_request_uses_base_rules_and_version_control(): void
    {
        $storeRequest = new StoreProductRequest;
        $updateRequest = new UpdateProductRequest;

        foreach ($storeRequest->rules() as $field => $rules) {
            $this->assertEquals($rules, $updateRequest->rules()[$field]);
        }

        $this->assertArrayHasKey('expected_moderation_version', $updateRequest->rules());
    }
}
