<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class CheckoutReturnController extends Controller
{
    public function success(Request $request, Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load('items.product', 'user');

        return view('checkout.success', compact('order'));
    }

    public function failure(Request $request, Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load('items.product', 'user');

        return view('checkout.failure', compact('order'));
    }

    public function pending(Request $request, Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load('items.product', 'user');

        return view('checkout.pending', compact('order'));
    }
}
