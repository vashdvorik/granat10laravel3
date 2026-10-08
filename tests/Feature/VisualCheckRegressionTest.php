<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Filament\Resources\BalanceTopupResource\Pages\ListBalanceTopups;
use App\Models\BalanceTopup;
use App\Models\Category;
use App\Models\Image;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Seller;
use App\Models\User;
use App\Services\CabinetDesignManager;
use App\Services\ProductVipService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Дефекты, найденные при проверке интерфейса в браузере: больше не должны возвращаться. */
class VisualCheckRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Seller $seller;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');
        $this->user = User::factory()->create(['balance' => 100]);
        $this->seller = Seller::factory()->create(['user_id' => $this->user->id]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function product(array $overrides = []): Product
    {
        $product = Product::factory()->create(array_merge([
            'seller_id' => $this->seller->id,
            'category_id' => Category::factory()->create()->id,
        ], $overrides));
        $image = Image::factory()->main()->create(['product_id' => $product->id]);
        Storage::disk('public')->put($image->path, 'x');

        return $product->refresh();
    }

    public function test_vip_page_does_not_say_product_was_unpublished_when_it_just_failed_review(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::ChangesRequired, 'is_active' => false,
            'moderated_version' => null, 'publication_requested' => true,
        ]);

        $state = app(ProductVipService::class)->state($product, $this->user);

        $codes = array_column($state->blockers, 'code');
        $this->assertContains('moderation_not_approved', $codes);
        // «Товар снят с публикации» продавцу ничего не говорит: он его не снимал
        $this->assertNotContains('not_enabled_by_seller', $codes);
    }

    public function test_vip_page_still_asks_to_publish_an_approved_but_unpublished_product(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Approved, 'is_active' => false,
            'moderation_version' => 1, 'moderated_version' => 1, 'publication_requested' => false,
        ]);

        $codes = array_column(app(ProductVipService::class)->state($product, $this->user)->blockers, 'code');

        $this->assertSame(['not_enabled_by_seller'], $codes);
    }

    public function test_draft_with_remark_shows_the_remark_on_the_card(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Draft, 'is_active' => false,
            'moderated_version' => null, 'moderation_summary' => 'Добавьте размеры в описание.',
        ]);

        $state = $product->cabinet_state;

        $this->assertSame('draft', $state->status);
        $this->assertSame('Замечания прошлой проверки: Добавьте размеры в описание.', $state->description);
    }

    public function test_push_up_date_is_shown_only_for_published_products(): void
    {
        app(CabinetDesignManager::class)->setActive('legacy');
        $this->product([
            'moderation_status' => ProductModerationStatus::Draft, 'is_active' => false,
            'moderated_version' => null, 'name' => 'Просто черновик',
        ]);

        $this->actingAs($this->user)->get(route('account.store.products.index'))
            ->assertOk()
            ->assertSee('Просто черновик')
            ->assertDontSee('Поднятие:');

        $this->product(['is_active' => true, 'name' => 'Живой товар']);

        $this->actingAs($this->user)->get(route('account.store.products.index'))
            ->assertSee('Поднятие:');
    }

    public function test_balance_page_highlights_its_menu_item_and_opens_account_group(): void
    {
        app(CabinetDesignManager::class)->setActive('legacy');

        $html = $this->actingAs($this->user)->get(route('account.balance.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<li class="has-child open active">\s*<a href="[^"]*account\/user"/', $html);
        $this->assertMatchesRegularExpression('/<li class="active">\s*<a href="[^"]*account\/balance">Баланс и операции/', $html);
    }

    public function test_profile_button_matches_whether_user_knows_a_password(): void
    {
        app(CabinetDesignManager::class)->setActive('legacy');

        $this->actingAs($this->user)->get(route('account.user.index'))
            ->assertSee('uil-lock"></i> Сменить пароль', false);

        $googleUser = User::factory()->create(['password_set_at' => null]);

        $this->actingAs($googleUser)->get(route('account.user.index'))
            ->assertSee('uil-lock"></i> Задать пароль', false)
            ->assertDontSee('uil-lock"></i> Сменить пароль', false);
    }

    public function test_vip_cancel_control_does_not_use_theme_btn_link_style(): void
    {
        $product = $this->product(['is_active' => true, 'is_vip' => true, 'vip_until' => now()->addDays(3)]);
        \App\Models\VipPurchase::query()->create([
            'product_id' => $product->id, 'user_id' => $this->user->id, 'days' => 7, 'price_cents' => 700,
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(6), 'status' => 'active',
        ]);
        $product->forceFill(['vip_until' => now()->addDays(6)])->save();

        $html = $this->actingAs($this->user)->get(route('account.vip.index'))->assertOk()->getContent();

        // в темах кабинета .btn-link получает цветной фон, поэтому «Отключить» — собственный класс
        $this->assertStringContainsString('class="vip-link-danger"', $html);
        $this->assertStringNotContainsString('btn-link', $html);
    }

    public function test_admin_payments_total_excludes_test_payments(): void
    {
        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));

        $role = Role::query()->create(['name' => 'admin-'.uniqid(), 'display_name' => 'Администратор']);
        $role->permissions()->attach(Permission::query()->firstOrCreate(['key' => 'browse_admin'], ['table_name' => null]));
        $admin = User::factory()->create(['role_id' => $role->id]);

        foreach ([[25000, false], [10000, true]] as [$cents, $isTest]) {
            BalanceTopup::query()->create([
                'user_id' => $this->user->id, 'amount_cents' => $cents, 'currency' => '000', 'status' => 'paid',
                'is_test' => $isTest, 'paid_at' => now(), 'expires_at' => now()->addMinutes(30),
            ]);
        }

        $this->actingAs($admin);

        Livewire::test(ListBalanceTopups::class)
            ->assertSee('Оплачено, без тестовых')
            ->assertSee('250,00 RUP')
            ->assertDontSee('350,00 RUP');
    }

    public function test_admin_record_titles_are_readable_in_breadcrumbs(): void
    {
        $topup = BalanceTopup::query()->create([
            'user_id' => $this->user->id, 'amount_cents' => 100, 'currency' => '000', 'status' => 'pending',
            'is_test' => false, 'expires_at' => now()->addMinutes(30),
        ]);

        $this->assertSame('Платёж № '.$topup->id, \App\Filament\Resources\BalanceTopupResource::getRecordTitle($topup));
    }
}
