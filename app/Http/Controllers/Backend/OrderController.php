<?php

namespace App\Http\Controllers\Backend;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\Seller;
use App\Services\OrderManagementService;
use App\Services\SellerCabinetStateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request, SellerCabinetStateService $sellerCabinetStateService): View
    {
        $this->authorize('viewAny', Order::class);
        $seller = $request->user()->seller;
        $status = $request->string('status')->toString();
        $sellerCabinetState = $sellerCabinetStateService->make($seller);
        $orderIntakeStatus = $this->orderIntakeStatus($seller);
        $statusCounts = Order::query()
            ->forSeller($seller)
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();
        $totalOrders = array_sum($statusCounts);
        $activeOrders = collect(OrderStatus::cases())
            ->reject(fn (OrderStatus $orderStatus): bool => $orderStatus->isFinal())
            ->sum(fn (OrderStatus $orderStatus): int => (int) ($statusCounts[$orderStatus->value] ?? 0));

        $orders = Order::query()
            ->forSeller($seller)
            ->when(
                in_array($status, array_column(OrderStatus::cases(), 'value'), true),
                fn ($query) => $query->where('status', $status),
            )
            ->recent()
            ->paginate(20)
            ->withQueryString();

        return view('backend.cabinet.orders.index', compact(
            'orders',
            'status',
            'sellerCabinetState',
            'orderIntakeStatus',
            'statusCounts',
            'totalOrders',
            'activeOrders',
        ));
    }

    public function show(Order $order, SellerCabinetStateService $sellerCabinetStateService): View
    {
        $this->authorize('view', $order);
        $order->load(['statusHistory.changedBy']);
        $seller = request()->user()->seller;
        $sellerCabinetState = $sellerCabinetStateService->make($seller);
        $orderIntakeStatus = $this->orderIntakeStatus($seller);

        return view('backend.cabinet.orders.show', compact('order', 'sellerCabinetState', 'orderIntakeStatus'));
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        OrderManagementService $orders,
    ): RedirectResponse {
        $this->authorize('updateStatus', $order);

        $orders->transition(
            $order,
            OrderStatus::from($request->string('status')->toString()),
            $request->user(),
            $request->input('comment'),
        );

        return back()->with('success', 'Статус заказа обновлён.');
    }

    private function orderIntakeStatus(Seller $seller): array
    {
        $activePackage = $seller->activePackage();
        $package = $activePackage?->package;

        if (! $activePackage) {
            return [
                'enabled' => false,
                'label' => 'Новые заказы недоступны',
                'description' => 'Нет активного тарифа. Уже созданные заказы остаются в кабинете и доступны для обработки.',
                'action' => route('account.packages.index'),
                'action_label' => 'Выбрать тариф',
            ];
        }

        if (! ($package?->has_order_system ?? false)) {
            return [
                'enabled' => false,
                'label' => 'Тариф не принимает новые заказы',
                'description' => 'История заказов сохраняется. Новые заказы через сайт появятся после перехода на тариф с заказами.',
                'action' => route('account.packages.index'),
                'action_label' => 'Сменить тариф',
            ];
        }

        if (! $seller->can_order) {
            return [
                'enabled' => false,
                'label' => 'Приём заказов выключен',
                'description' => 'Тариф поддерживает заказы, но магазин выключил форму заказа в настройках.',
                'action' => route('account.store.edit', $seller),
                'action_label' => 'Открыть настройки',
            ];
        }

        return [
            'enabled' => true,
            'label' => 'Приём заказов включён',
            'description' => 'Покупатели могут оформить заказ у товаров, где заказ разрешён.',
            'action' => route('account.store.edit', $seller),
            'action_label' => 'Настроить заказы',
        ];
    }
}
