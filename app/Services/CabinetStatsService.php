<?php

namespace App\Services;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CabinetStatsService
{
    public function getStats(User $user, Seller $seller): array
    {
        $productIds = $seller->products()->pluck('id');

        $totalProducts = $productIds->count();
        $activeProducts = $seller->products()->where('is_active', true)->count();
        $inactiveProducts = $totalProducts - $activeProducts;
        $vipProducts = $seller->products()
            ->where('is_active', true)
            ->where('is_vip', true)
            ->count();
        $saleProducts = $seller->products()
            ->whereNotNull('lowprice')
            ->where('lowprice', '>', 0)
            ->count();

        $totalViews = $productIds->isEmpty()
            ? 0
            : DB::table('views')->whereIn('product_id', $productIds)->sum('views');

        $topProducts = $productIds->isEmpty()
            ? collect()
            : DB::table('products')
                ->join('views', 'products.id', '=', 'views.product_id')
                ->whereIn('products.id', $productIds)
                ->select('products.id', 'products.name', 'products.is_active', 'views.views')
                ->orderByDesc('views.views')
                ->limit(5)
                ->get();

        return [
            'seller' => $seller,
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'inactiveProducts' => $inactiveProducts,
            'vipProducts' => $vipProducts,
            'saleProducts' => $saleProducts,
            'totalViews' => $totalViews,
            'topProducts' => $topProducts,
            'balance' => $user->balance,
        ];
    }
}
