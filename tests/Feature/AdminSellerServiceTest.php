<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Image;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Seller;
use App\Models\User;
use App\Services\AdminSellerService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminSellerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_administrator_deletes_seller_relations_files_and_logs_operation(): void
    {
        $administrator = $this->createAdministrator();
        $owner = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $owner->id,
            'photo' => 'sellers/logo.webp',
            'photos' => json_encode([
                'sellers/gallery-1.webp',
                'sellers/gallery-2.webp',
            ]),
        ]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => Category::factory(),
        ]);
        $image = Image::query()->create([
            'product_id' => $product->id,
            'path' => 'products/image.webp',
            'is_main' => true,
        ]);

        foreach ([
            $seller->photo,
            'sellers/gallery-1.webp',
            'sellers/gallery-2.webp',
            $image->path,
        ] as $path) {
            Storage::disk('public')->put($path, 'file');
        }

        $sellerName = app(AdminSellerService::class)->delete(
            $administrator,
            $seller,
        );

        $this->assertSame($seller->name, $sellerName);
        $this->assertDatabaseMissing('sellers', ['id' => $seller->id]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);

        Storage::disk('public')->assertMissing($seller->photo);
        Storage::disk('public')->assertMissing('sellers/gallery-1.webp');
        Storage::disk('public')->assertMissing('sellers/gallery-2.webp');
        Storage::disk('public')->assertMissing($image->path);

        $activity = Activity::query()
            ->where('log_name', 'sellers')
            ->where('description', 'Административное удаление магазина')
            ->firstOrFail();

        $this->assertSame($administrator->id, $activity->causer_id);
        $this->assertSame($seller->id, $activity->properties->get('seller_id'));
    }

    public function test_regular_user_cannot_delete_other_seller(): void
    {
        $actor = User::factory()->create();
        $seller = Seller::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(AdminSellerService::class)->delete($actor, $seller);
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
