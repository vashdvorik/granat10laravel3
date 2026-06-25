<?php

namespace App\Http\Controllers\Backend;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);
        $seller = $request->user()->seller;
        $status = $request->string('status')->toString();

        $orders = Order::query()
            ->forSeller($seller)
            ->when(
                in_array($status, array_column(OrderStatus::cases(), 'value'), true),
                fn ($query) => $query->where('status', $status),
            )
            ->recent()
            ->paginate(20)
            ->withQueryString();

        return view('backend.cabinet.orders.index', compact('orders', 'status'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['statusHistory.changedBy']);

        return view('backend.cabinet.orders.show', compact('order'));
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
}
