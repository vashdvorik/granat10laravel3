<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\VipPurchase;
use App\Services\ProductVipService;
use App\Services\VipPricing;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/** VIP-размещение в кабинете: общая страница, покупка/продление и досрочное отключение. */
class VipController extends Controller
{
    public function __construct(
        private readonly ProductVipService $vip,
        private readonly VipPricing $pricing,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $seller = $user->seller;

        if ($seller === null) {
            return redirect()->route('account.store.create')
                ->with('error', 'VIP-размещение доступно после создания магазина.');
        }

        $products = $seller->products()
            ->with('images')
            ->orderByVipFirst()
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $rows = $products->getCollection()->map(fn (Product $product): array => [
            'product' => $product,
            'state' => $this->vip->state($product, $user),
        ]);

        $productIds = $seller->products()->pluck('id');

        return view('backend.cabinet.vip.index', [
            'user' => $user,
            'products' => $products,
            'rows' => $rows,
            'activeCount' => $seller->products()->vipActive()->count(),
            'expiringCount' => $seller->products()->vipActive()
                ->whereNotNull('vip_until')->where('vip_until', '<=', now()->addDays(3))->count(),
            'pricePerDayCents' => $this->pricing->pricePerDayInCents(),
            'durations' => $this->pricing->options(),
            'history' => VipPurchase::query()
                ->whereIn('product_id', $productIds)
                ->with('product')
                ->latest('id')
                ->limit(20)
                ->get(),
            'balanceCents' => (int) round(((float) $user->getRawOriginal('balance')) * 100),
        ]);
    }

    public function purchase(Request $request, Product $product)
    {
        $this->authorize('view', $product);

        $validated = $request->validate([
            'days' => ['required', 'integer', Rule::in(array_keys($this->pricing->durations()))],
        ], [
            'days.required' => 'Выберите срок VIP.',
            'days.in' => 'Выбран недоступный срок VIP.',
            'days.integer' => 'Выбран недоступный срок VIP.',
        ]);

        $wasActive = $product->isVipActive();

        try {
            $updated = $this->vip->purchase($request->user(), $product, (int) $validated['days']);
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        $balance = (int) round(((float) $request->user()->fresh()->getRawOriginal('balance')) * 100);

        return redirect()->back()->with(
            'success',
            ($wasActive ? 'VIP продлён' : 'VIP включён').' для товара «'.$updated->name.'»: действует до '
            .$updated->vip_until->format('d.m.Y H:i').'. Списано '
            .Money::rubles($this->pricing->priceInCents((int) $validated['days'])).', на балансе '.Money::rubles($balance).'.'
        );
    }

    public function cancel(Request $request, Product $product)
    {
        $this->authorize('view', $product);

        try {
            $refund = $this->vip->cancel($request->user(), $product);
        } catch (RuntimeException $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with(
            'success',
            'VIP для товара «'.$product->name.'» отключён.'
            .($refund > 0 ? ' На баланс возвращено '.Money::rubles($refund).'.' : ' Возврат не положен: неиспользованных полных дней нет.')
        );
    }
}
