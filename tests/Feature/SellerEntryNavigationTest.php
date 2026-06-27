<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerEntryNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_seller_entry_copy_and_client_mobile_nav(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Войти в кабинет продавца')
            ->assertSee('Войти продавцу')
            ->assertSee('Стать продавцом')
            ->assertSee('Вернуться в маркетплейс')
            ->assertSee('.mobile-bottom-navbar', false)
            ->assertSee(route('home'), false)
            ->assertSee(route('catalog.index'), false)
            ->assertSee(route('search'), false)
            ->assertSee(route('favorites'), false)
            ->assertSee(route('shops'), false)
            ->assertDontSee("querySelector('.mobile-bottom-navbar')", false)
            ->assertDontSee('Кабинет</span>', false)
            ->assertDontSee('Распродажи</span>', false);
    }

    public function test_register_page_uses_seller_registration_copy(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Стать продавцом')
            ->assertSee('Создать кабинет продавца')
            ->assertSee('Уже есть кабинет продавца')
            ->assertSee('Войти продавцу')
            ->assertDontSee('Распродажи</span>', false);
    }

    public function test_cabinet_has_visible_marketplace_return_links(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('cabinet-marketplace-top-link', false)
            ->assertSee('marketplace-return-link', false)
            ->assertSee('Вернуться в маркетплейс');
    }
}
