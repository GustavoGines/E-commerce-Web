<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class CheckoutReturnController extends Controller
{
    public function success(Request $request, Order $order)
    {
        return $this->processReturn($order, 'checkout.success');
    }

    public function failure(Request $request, Order $order)
    {
        return $this->processReturn($order, 'checkout.failure');
    }

    public function pending(Request $request, Order $order)
    {
        return $this->processReturn($order, 'checkout.pending');
    }

    private function processReturn(Order $order, string $view)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load('items.product', 'user');

        return view($view, compact('order'));
    }
}
