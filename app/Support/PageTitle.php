<?php

namespace App\Support;

/**
 * Заголовки (`<title>`) страниц: везде один вид «Страница — BIMM», в кабинете — «Страница — Кабинет BIMM».
 * Название бренда берётся из `config('app.name')`, поэтому в коде оно не повторяется.
 */
class PageTitle
{
    public const SEPARATOR = ' — ';

    /** Название сайта: «BIMM». */
    public static function brand(): string
    {
        return (string) config('app.name');
    }

    /** «Каталог товаров — BIMM». Без названия страницы — просто бренд. */
    public static function site(?string $page): string
    {
        $page = trim((string) $page);

        return $page === '' ? self::brand() : $page.self::SEPARATOR.self::brand();
    }

    /** «Товары — Кабинет BIMM». Страница определяется по названию маршрута, если `$page` не задан. */
    public static function cabinet(?string $page, ?string $routeName = null): string
    {
        $page = trim((string) $page);

        if ($page === '') {
            $page = self::cabinetPage($routeName);
        }

        return $page.self::SEPARATOR.'Кабинет '.self::brand();
    }

    /** Название страницы кабинета по маршруту; для неизвестных маршрутов — «Кабинет продавца». */
    public static function cabinetPage(?string $routeName): string
    {
        return match ($routeName) {
            'account.index' => 'Обзор магазина',
            'account.stats' => 'Аналитика',
            'account.vip.index' => 'VIP-размещение',
            'account.balance.index' => 'Журнал баланса',
            'account.store.create' => 'Создание магазина',
            'account.store.delete' => 'Удаление магазина',
            'account.store.edit' => 'Настройки магазина',
            'account.store.products.index' => 'Товары',
            'account.store.products.create' => 'Новый товар',
            'account.store.products.show' => 'Карточка товара',
            'account.store.products.edit' => 'Редактирование товара',
            'account.store.orders.index' => 'Заказы',
            'account.store.orders.show' => 'Заказ',
            'account.store.events.index' => 'Журнал событий',
            'account.store.qr' => 'QR-код магазина',
            'account.store.telegram' => 'Telegram-уведомления',
            'account.user.index' => 'Профиль',
            'account.user.edit' => 'Редактирование профиля',
            'account.user.change_password' => 'Безопасность',
            'account.user.balance.topup' => 'Пополнение баланса',
            default => 'Кабинет продавца',
        };
    }
}
