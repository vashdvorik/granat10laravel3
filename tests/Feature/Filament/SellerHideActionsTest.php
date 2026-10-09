<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SellerResource\Pages\EditSeller;
use App\Filament\Resources\SellerResource\Pages\ListSellers;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Seller;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** /admin/sellers: все действия — в одной кнопке «Действия», плюс «Скрыть магазин» с обязательной причиной. */
class SellerHideActionsTest extends TestCase
{
    use RefreshDatabase;

    private const REASON = 'Название магазина нарушает правила площадки.';

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));

        $role = Role::query()->create(['name' => 'admin-'.uniqid(), 'display_name' => 'Администратор']);
        $role->permissions()->attach(Permission::query()->create(['key' => 'browse_admin', 'table_name' => null]));
        $this->administrator = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($this->administrator);
    }

    private function hidden(array $overrides = []): Seller
    {
        $seller = Seller::factory()->create($overrides);
        $seller->forceFill(['hidden_at' => now(), 'hidden_reason' => self::REASON, 'hidden_by_user_id' => $this->administrator->id])->save();

        return $seller;
    }

    public function test_all_row_actions_are_collected_in_one_button(): void
    {
        $seller = Seller::factory()->create();

        $table = Livewire::test(ListSellers::class)->assertCanSeeTableRecords([$seller])->instance()->getTable();
        $actions = $table->getActions();

        $this->assertCount(1, $actions, 'в строке должна быть одна кнопка, а не ряд кнопок');
        $group = reset($actions);
        $this->assertInstanceOf(ActionGroup::class, $group);
        $this->assertSame('Действия', $group->getLabel());

        $names = array_map(fn ($action) => $action->getName(), array_values($group->getActions()));
        $this->assertSame(['publicPage', 'products', 'edit', 'hideSeller', 'changeHiddenReason', 'showSeller', 'deleteSeller'], $names);
    }

    public function test_alias_column_is_hidden_by_default_to_keep_the_table_narrow(): void
    {
        $column = Livewire::test(ListSellers::class)->instance()->getTable()->getColumn('alias');

        $this->assertTrue($column->isToggledHiddenByDefault());
    }

    public function test_hide_action_requires_a_reason_and_hides_the_shop(): void
    {
        $seller = Seller::factory()->create();

        Livewire::test(ListSellers::class)
            ->callTableAction('hideSeller', $seller, ['reason' => ''])
            ->assertHasTableActionErrors(['reason' => ['required']]);

        $this->assertFalse($seller->refresh()->isHidden());

        Livewire::test(ListSellers::class)
            ->callTableAction('hideSeller', $seller, ['reason' => self::REASON])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Магазин скрыт');

        $seller->refresh();
        $this->assertTrue($seller->isHidden());
        $this->assertSame(self::REASON, $seller->hidden_reason);
        $this->assertSame($this->administrator->id, $seller->hidden_by_user_id);
    }

    public function test_actions_depend_on_the_current_state(): void
    {
        $visible = Seller::factory()->create();
        $hidden = $this->hidden();

        Livewire::test(ListSellers::class)
            ->assertTableActionVisible('hideSeller', $visible)
            ->assertTableActionVisible('publicPage', $visible)
            ->assertTableActionHidden('changeHiddenReason', $visible)
            ->assertTableActionHidden('showSeller', $visible)
            ->assertTableActionHidden('hideSeller', $hidden)
            ->assertTableActionHidden('publicPage', $hidden)
            ->assertTableActionVisible('changeHiddenReason', $hidden)
            ->assertTableActionVisible('showSeller', $hidden);
    }

    public function test_change_reason_prefills_current_text_and_saves_new_one(): void
    {
        $seller = $this->hidden();

        Livewire::test(ListSellers::class)
            ->mountTableAction('changeHiddenReason', $seller)
            ->assertTableActionDataSet(['reason' => self::REASON])
            ->setTableActionData(['reason' => 'Новая причина'])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertSame('Новая причина', $seller->refresh()->hidden_reason);
        $this->assertTrue($seller->isHidden());
    }

    public function test_show_action_returns_the_shop_and_clears_reason(): void
    {
        $seller = $this->hidden();

        Livewire::test(ListSellers::class)
            ->callTableAction('showSeller', $seller)
            ->assertNotified('Магазин снова виден покупателям');

        $seller->refresh();
        $this->assertFalse($seller->isHidden());
        $this->assertNull($seller->hidden_reason);
    }

    public function test_delete_action_still_removes_the_shop_from_the_group(): void
    {
        $seller = Seller::factory()->create();
        Product::factory()->create(['seller_id' => $seller->id]);

        Livewire::test(ListSellers::class)->callTableAction('deleteSeller', $seller);

        $this->assertNull(Seller::find($seller->id));
    }

    public function test_status_column_and_filter_show_hidden_shops(): void
    {
        $visible = Seller::factory()->create();
        $hidden = $this->hidden();

        Livewire::test(ListSellers::class)
            ->assertTableColumnStateSet('hidden_at', 'Виден', $visible)
            ->assertTableColumnStateSet('hidden_at', 'Скрыт', $hidden)
            ->filterTable('hidden', true)
            ->assertCanSeeTableRecords([$hidden])
            ->assertCanNotSeeTableRecords([$visible])
            ->filterTable('hidden', false)
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_edit_page_has_the_same_actions_and_shows_the_reason(): void
    {
        $seller = Seller::factory()->create();

        $page = Livewire::test(EditSeller::class, ['record' => $seller->getRouteKey()])
            ->assertActionVisible('hideSeller')
            ->assertActionHidden('showSeller')
            ->callAction('hideSeller', ['reason' => ''])
            ->assertHasActionErrors(['reason' => ['required']])
            ->callAction('hideSeller', ['reason' => self::REASON])
            ->assertHasNoActionErrors();

        $this->assertTrue($seller->refresh()->isHidden());

        $page->assertActionHidden('hideSeller')->assertActionVisible('showSeller');

        $this->get("/admin/sellers/{$seller->id}/edit")
            ->assertOk()
            ->assertSee('Магазин скрыт от покупателей')
            ->assertSee(self::REASON);

        Livewire::test(EditSeller::class, ['record' => $seller->getRouteKey()])->callAction('showSeller');

        $this->assertFalse($seller->refresh()->isHidden());
        $this->get("/admin/sellers/{$seller->id}/edit")->assertDontSee('Магазин скрыт от покупателей');
    }
}
