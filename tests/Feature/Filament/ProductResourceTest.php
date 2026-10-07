<?php

namespace Tests\Feature\Filament;

use App\Enums\ProductModerationStatus;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\RelationManagers\ImagesRelationManager;
use App\Models\Category;
use App\Models\Image;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Models\Role;
use App\Models\Seller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
        Storage::fake('public');
    }

    public function test_administrator_can_open_product_resource(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)
            ->get('/admin/products')
            ->assertOk();
    }

    public function test_regular_user_cannot_open_product_resource(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_product_list_supports_search_and_filters(): void
    {
        $administrator = $this->createAdministrator();
        $firstSeller = Seller::factory()->create();
        $secondSeller = Seller::factory()->create();
        $category = Category::factory()->create();
        $firstProduct = Product::factory()->create([
            'name' => 'Уникальный товар',
            'seller_id' => $firstSeller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'is_vip' => true,
            'lowprice' => 90,
        ]);
        $secondProduct = Product::factory()->create([
            'name' => 'Обычный товар',
            'seller_id' => $secondSeller->id,
            'category_id' => $category->id,
            'is_active' => false,
            'is_vip' => false,
            'lowprice' => null,
        ]);

        $this->actingAs($administrator);

        Livewire::test(ListProducts::class)
            ->searchTable('Уникальный')
            ->assertCanSeeTableRecords([$firstProduct])
            ->assertCanNotSeeTableRecords([$secondProduct])
            ->searchTable()
            ->filterTable('seller_id', $firstSeller->id)
            ->assertCanSeeTableRecords([$firstProduct])
            ->assertCanNotSeeTableRecords([$secondProduct])
            ->resetTableFilters()
            ->filterTable('is_vip', true)
            ->assertCanSeeTableRecords([$firstProduct])
            ->assertCanNotSeeTableRecords([$secondProduct])
            ->resetTableFilters()
            ->filterTable('on_sale', true)
            ->assertCanSeeTableRecords([$firstProduct])
            ->assertCanNotSeeTableRecords([$secondProduct]);
    }

    public function test_product_list_filters_image_moderation_problems(): void
    {
        $administrator = $this->createAdministrator();
        $problemProduct = Product::factory()->create([
            'moderation_version' => 3,
            'moderation_status' => ProductModerationStatus::NeedsReview,
        ]);
        $cleanProduct = Product::factory()->create([
            'moderation_version' => 3,
            'moderation_status' => ProductModerationStatus::Approved,
        ]);

        ProductModerationRun::create([
            'product_id' => $problemProduct->id,
            'content_version' => 3,
            'trigger' => 'image_queue',
            'provider' => 'image_google',
            'status' => 'completed',
            'decision' => 'review',
            'request_fingerprint' => hash('sha256', 'problem-image'),
        ]);
        ProductModerationRun::create([
            'product_id' => $cleanProduct->id,
            'content_version' => 3,
            'trigger' => 'image_queue',
            'provider' => 'image_google',
            'status' => 'completed',
            'decision' => 'allow',
            'request_fingerprint' => hash('sha256', 'clean-image'),
        ]);

        $this->actingAs($administrator);

        Livewire::test(ListProducts::class)
            ->filterTable('image_moderation_problem', true)
            ->assertCanSeeTableRecords([$problemProduct])
            ->assertCanNotSeeTableRecords([$cleanProduct])
            ->resetTableFilters()
            ->filterTable('image_moderation_problem', false)
            ->assertCanSeeTableRecords([$cleanProduct])
            ->assertCanNotSeeTableRecords([$problemProduct]);
    }

    public function test_administrator_can_create_inactive_product_through_write_service(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($administrator);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'seller_id' => $seller->id,
                'category_id' => $category->id,
                'name' => 'Новый товар',
                'price' => 1000,
                'lowprice' => 900,
                'is_active' => false,
                'is_vip' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $product = Product::query()->where('name', 'Новый товар')->firstOrFail();

        $this->assertSame($seller->id, $product->seller_id);
        $this->assertSame($category->id, $product->category_id);
        $this->assertNotEmpty($product->alias);
        $this->assertNotNull($product->sale_started_at);
        $this->assertFalse((bool) $product->is_active);
    }

    public function test_create_rejects_non_leaf_category(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($administrator);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'seller_id' => $seller->id,
                'category_id' => $parent->id,
                'name' => 'Неверная категория',
                'price' => 100,
                'is_active' => false,
                'is_vip' => false,
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertDatabaseMissing('products', ['name' => 'Неверная категория']);
    }

    public function test_active_product_can_be_created_with_image_and_available_limit(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($administrator);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'seller_id' => $seller->id,
                'category_id' => $category->id,
                'name' => 'Активный товар',
                'price' => 500,
                'is_active' => true,
                'is_vip' => false,
                'images' => [
                    UploadedFile::fake()->image('product.jpg', 800, 800),
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $product = Product::query()->where('name', 'Активный товар')->firstOrFail();
        $image = $product->images()->firstOrFail();

        $this->assertFalse((bool) $product->is_active);
        $this->assertTrue($product->publication_requested);
        $this->assertTrue((bool) $image->is_main);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_edit_uses_write_service_and_does_not_change_seller(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();
        $otherSeller = Seller::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->inactive()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'lowprice' => null,
        ]);

        $this->actingAs($administrator);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormFieldIsDisabled('seller_id')
            ->assertFormFieldIsDisabled('alias')
            ->fillForm([
                'seller_id' => $otherSeller->id,
                'category_id' => $category->id,
                'name' => 'Обновлённый товар',
                'price' => 1000,
                'lowprice' => 800,
                'is_active' => false,
                'is_vip' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $product->refresh();

        $this->assertSame($seller->id, $product->seller_id);
        $this->assertSame('Обновлённый товар', $product->name);
        $this->assertTrue((bool) $product->is_vip);
        $this->assertNotNull($product->sale_started_at);
    }

    public function test_publish_action_rejects_product_without_image_and_publishes_valid_product(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $invalidProduct = Product::factory()->inactive()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);
        $validProduct = Product::factory()->inactive()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);
        Image::factory()->create(['product_id' => $validProduct->id]);

        $this->actingAs($administrator);

        Livewire::test(ListProducts::class)
            ->callTableAction('activate', $invalidProduct)
            ->assertTableActionHalted('activate');

        $this->assertFalse((bool) $invalidProduct->refresh()->is_active);

        Livewire::test(ListProducts::class)
            ->callTableAction('activate', $validProduct)
            ->assertNotified();

        $this->assertTrue((bool) $validProduct->refresh()->is_active);
    }

    public function test_publish_action_has_no_limit_on_active_or_vip_products(): void
    {
        $administrator = $this->createAdministrator();
        $category = Category::factory()->create();
        $seller = Seller::factory()->create();

        Product::factory()->count(12)->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        Product::factory()->activeVip()->count(3)->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);
        $product = Product::factory()->inactive()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);
        Image::factory()->create(['product_id' => $product->id]);

        $this->actingAs($administrator);

        Livewire::test(ListProducts::class)
            ->callTableAction('activate', $product)
            ->assertNotified();

        $this->assertTrue((bool) $product->refresh()->is_active);
    }

    public function test_moderator_can_approve_request_changes_and_reject_products(): void
    {
        $administrator = $this->createAdministrator();
        $approved = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $changes = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $rejected = Product::factory()->pendingModeration()->create(['is_active' => true]);

        $this->actingAs($administrator);

        Livewire::test(ListProducts::class)
            ->callTableAction('approveModeration', $approved, [
                'comment' => 'Одобрено вручную',
            ])
            ->assertNotified();

        Livewire::test(ListProducts::class)
            ->callTableAction('requestChanges', $changes, [
                'comment' => 'Добавьте описание',
            ])
            ->assertNotified();

        Livewire::test(ListProducts::class)
            ->callTableAction('rejectModeration', $rejected, [
                'reason_code' => 'prohibited_product',
                'comment' => 'Запрещённый товар',
            ])
            ->assertNotified();

        $this->assertSame(ProductModerationStatus::Approved, $approved->fresh()->moderation_status);
        $this->assertSame(ProductModerationStatus::ChangesRequired, $changes->fresh()->moderation_status);
        $this->assertSame(ProductModerationStatus::Rejected, $rejected->fresh()->moderation_status);
        $this->assertFalse((bool) $changes->fresh()->is_active);
        $this->assertFalse((bool) $rejected->fresh()->is_active);
    }

    public function test_moderator_can_approve_product_after_provider_failure(): void
    {
        $administrator = $this->createAdministrator();
        $product = Product::factory()->create([
            'moderation_status' => ProductModerationStatus::NeedsReview,
            'moderation_version' => 2,
            'moderated_version' => null,
            'is_active' => false,
        ]);

        $this->actingAs($administrator);

        Livewire::test(ListProducts::class)
            ->assertTableActionVisible('approveModeration', $product)
            ->callTableAction('approveModeration', $product, [
                'comment' => 'Проверено администратором после сбоя ИИ',
            ])
            ->assertNotified();

        $product->refresh();

        $this->assertSame(ProductModerationStatus::Approved, $product->moderation_status);
        $this->assertSame(2, $product->moderated_version);
    }

    public function test_deactivate_push_and_delete_actions_use_action_service(): void
    {
        $administrator = $this->createAdministrator();
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'push_at' => now()->subHours(25),
        ]);
        $image = Image::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/action-delete.webp',
        ]);
        Storage::disk('public')->put($image->path, 'image');

        $this->actingAs($administrator);

        $oldPushAt = $product->push_at;

        Livewire::test(ListProducts::class)
            ->callTableAction('pushUp', $product)
            ->assertNotified();

        $this->assertTrue($product->refresh()->push_at->greaterThan($oldPushAt));

        Livewire::test(ListProducts::class)
            ->callTableAction('deactivate', $product)
            ->assertNotified();

        $this->assertFalse((bool) $product->refresh()->is_active);

        Livewire::test(ListProducts::class)
            ->callTableAction('deleteProduct', $product)
            ->assertNotified();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_images_relation_manager_sets_main_and_deletes_image_safely(): void
    {
        $administrator = $this->createAdministrator();
        $product = Product::factory()->inactive()->create();
        $oldMain = Image::factory()->main()->create([
            'product_id' => $product->id,
            'path' => 'products/old-main.webp',
            'order' => 1,
        ]);
        $newMain = Image::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/new-main.webp',
            'order' => 2,
        ]);
        Storage::disk('public')->put($oldMain->path, 'old');
        Storage::disk('public')->put($newMain->path, 'new');

        $this->actingAs($administrator);

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => EditProduct::class,
        ])
            ->callTableAction('setMain', $newMain)
            ->assertNotified()
            ->callTableAction('deleteImage', $oldMain)
            ->assertNotified();

        $this->assertTrue((bool) $newMain->refresh()->is_main);
        $this->assertDatabaseMissing('images', ['id' => $oldMain->id]);
        Storage::disk('public')->assertMissing($oldMain->path);
    }

    public function test_images_relation_manager_uploads_files_with_order_and_limit(): void
    {
        $administrator = $this->createAdministrator();
        $product = Product::factory()->inactive()->create();

        $this->actingAs($administrator);

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => EditProduct::class,
        ])
            ->callTableAction('uploadImages', null, [
                'images' => [
                    UploadedFile::fake()->image('first.jpg', 800, 800),
                    UploadedFile::fake()->image('second.jpg', 800, 800),
                ],
            ])
            ->assertNotified();

        $images = $product->images()->orderBy('order')->get();

        $this->assertCount(2, $images);
        $this->assertTrue((bool) $images->first()->is_main);
        $this->assertSame([1, 2], $images->pluck('order')->all());
        Storage::disk('public')->assertExists($images[0]->path);
        Storage::disk('public')->assertExists($images[1]->path);
    }

    private function createAdministrator(): User
    {
        $role = Role::query()->create([
            'name' => 'admin-'.uniqid(),
            'display_name' => 'Администратор',
        ]);
        $permission = Permission::query()->create([
            'key' => 'browse_admin',
            'table_name' => null,
        ]);
        $role->permissions()->attach($permission);

        return User::factory()->create(['role_id' => $role->id]);
    }

}
