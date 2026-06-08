<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\SellerPackageResource\Pages\ListSellerPackages;
use App\Filament\Resources\SellerResource\Pages\CreateSeller;
use App\Models\Category;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdministrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
        Storage::fake('public');
    }

    public function test_administrator_can_complete_seller_product_and_subscription_workflow(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create(['balance' => 500]);
        $basePackage = Package::factory()->free()->create([
            'id' => 1,
            'name' => 'Базовый',
        ]);
        $paidPackage = Package::factory()->withLimits(10, 3)->create([
            'name' => 'Профессиональный',
            'price' => 100,
            'duration_days' => 30,
        ]);
        $category = Category::factory()->create(['name' => 'Тестовая категория']);

        $this->actingAs($administrator);

        Livewire::test(CreateSeller::class)
            ->fillForm([
                'user_id' => $owner->id,
                'name' => 'Тестовый магазин',
                'logo' => UploadedFile::fake()->image('seller-logo.png', 800, 800),
                'phone' => '+37360000000',
                'town' => 'Кишинёв',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $seller = Seller::query()->where('user_id', $owner->id)->firstOrFail();
        $baseSubscription = SellerPackage::query()
            ->where('seller_id', $seller->id)
            ->where('package_id', $basePackage->id)
            ->firstOrFail();

        $this->assertNotNull($seller->photo);
        Storage::disk('public')->assertExists($seller->photo);
        $this->assertTrue($baseSubscription->is_active);

        Livewire::test(ListSellerPackages::class)
            ->callTableAction('assignPackage', null, [
                'seller_id' => $seller->id,
                'package_id' => $paidPackage->id,
                'charge_balance' => true,
                'reason' => 'Проверка полного сценария Filament',
            ])
            ->assertNotified();

        $activeSubscription = SellerPackage::query()
            ->where('seller_id', $seller->id)
            ->where('package_id', $paidPackage->id)
            ->firstOrFail();

        $this->assertTrue((bool) $activeSubscription->is_active);
        $this->assertFalse($baseSubscription->fresh()->is_active);
        $this->assertSame(400.0, (float) $owner->fresh()->balance);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'seller_id' => $seller->id,
                'category_id' => $category->id,
                'name' => 'Тестовый товар',
                'price' => 250,
                'lowprice' => 200,
                'is_active' => true,
                'is_vip' => true,
                'images' => [
                    UploadedFile::fake()->image('product.png', 1000, 1000),
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $product = Product::query()->where('name', 'Тестовый товар')->firstOrFail();
        $image = $product->images()->firstOrFail();

        $this->assertTrue((bool) $product->is_active);
        $this->assertTrue((bool) $product->is_vip);
        $this->assertTrue((bool) $image->is_main);
        Storage::disk('public')->assertExists($image->path);

        $this->get("/admin/sellers/{$seller->id}/edit")
            ->assertOk()
            ->assertSee('Товары')
            ->assertSee('История тарифов');

        $this->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('Изображения');

        $this->get('/admin/activity-log')
            ->assertOk()
            ->assertSee('Административное назначение тарифа');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'packages',
            'description' => 'Административное назначение тарифа',
            'causer_id' => $administrator->id,
        ]);
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
