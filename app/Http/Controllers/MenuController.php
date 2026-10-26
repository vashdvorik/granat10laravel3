<?php

namespace App\Http\Controllers;

use App\Services\CatalogCacheService;
use Illuminate\Http\Response;

class MenuController extends Controller
{
    /**
     * Полное дерево категорий для бокового меню телефона. Меню подгружает его при первом касании кнопки (public/js/drawer-menu.js),
     * поэтому 230 КБ HTML не приходят с каждой страницей. Готовый HTML лежит в кеше отдельно для каждого города
     * и сбрасывается вместе с данными меню; браузер держит ответ 5 минут.
     */
    public function categories(CatalogCacheService $cache): Response
    {
        $html = $cache->rememberMenuHtml(
            fn (): string => view('components.menu-tree-full')->render()
        );

        return response($html)->header('Cache-Control', 'private, max-age=300');
    }
}
