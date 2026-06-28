<?php

namespace App\Http\Controllers;

use App\Data\OrderCreationData;
use App\Exceptions\OrderUnavailableException;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\CustomerOrderListService;
use App\Services\OrderService;
use App\Services\OrderTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function store(
        StoreOrderRequest $request,
        Product $product,
        OrderService $orders,
        CustomerOrderListService $customerOrders,
    ): RedirectResponse {
        try {
            $created = $orders->create($product, new OrderCreationData(
                customerName: $request->string('customer_name')->toString(),
                customerPhone: $request->string('customer_phone')->toString(),
                quantity: $request->integer('quantity'),
                fulfillmentMethod: $request->string('fulfillment_method')->toString(),
                deliveryAddress: $request->input('delivery_address'),
                customerComment: $request->input('customer_comment'),
                consentVersion: 'order-form-v1',
                idempotencyKey: $request->string('idempotency_key')->toString(),
                sourceIp: $request->ip(),
            ));
        } catch (OrderUnavailableException $exception) {
            return back()
                ->withInput($request->except('idempotency_key'))
                ->withErrors(['order' => $exception->getMessage()]);
        }

        $request->session()->put(
            $this->sessionKey($created->order->public_id),
            $created->accessToken,
        );
        $customerOrders->remember($request, $created->order->public_id, $created->accessToken);

        return redirect()->route('orders.show', $created->order->public_id);
    }

    public function index(
        Request $request,
        CustomerOrderListService $customerOrders,
        OrderTokenService $tokens,
    ): View {
        $orders = $customerOrders->authorizedOrders($request, $tokens);

        return view('orders.index', compact('orders'));
    }

    public function show(
        Request $request,
        string $publicId,
        OrderTokenService $tokens,
        CustomerOrderListService $customerOrders,
    ): View {
        $order = Order::query()
            ->where('public_id', $publicId)
            ->firstOrFail();
        $accessToken = $request->session()->get($this->sessionKey($publicId))
            ?? $customerOrders->accessTokenFor($request, $publicId);

        abort_unless(
            is_string($accessToken)
            && hash_equals($order->access_token_hash, $tokens->accessTokenHash($accessToken)),
            404,
        );

        return view('orders.show', compact('order'));
    }

    public function destroy(
        Request $request,
        string $publicId,
        CustomerOrderListService $customerOrders,
    ): RedirectResponse {
        $customerOrders->forget($request, $publicId);

        return redirect()
            ->route('orders.index')
            ->with('status', 'Заказ удалён из списка.');
    }

    public function clear(Request $request, CustomerOrderListService $customerOrders): RedirectResponse
    {
        $customerOrders->clear($request);

        return redirect()
            ->route('orders.index')
            ->with('status', 'Список заказов очищен.');
    }

    private function sessionKey(string $publicId): string
    {
        return 'order_access.'.$publicId;
    }
}
