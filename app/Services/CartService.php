<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    /**
     * Get the current cart instance (creates one if it doesn't exist).
     */
    public function getCart()
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $sessionId = Session::getId();

        return Cart::firstOrCreate(['session_id' => $sessionId, 'user_id' => null]);
    }

    /**
     * Get the cart items mapped as [product_id => quantity] for easier frontend migration.
     */
    public function getCartItemsArray()
    {
        $cart = $this->getCart();

        return $cart->items()->pluck('quantity', 'product_id')->toArray();
    }

    /**
     * Add or increment an item in the cart.
     */
    public function addItem($productId, $quantity = 1)
    {
        $cart = $this->getCart();
        $product = Product::find($productId);

        if (! $product) {
            return false;
        }

        $cartItem = $cart->items()->where('product_id', $productId)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            if ($newQuantity <= $product->stock) {
                $cartItem->update(['quantity' => $newQuantity]);

                return true;
            }

            return false; // Not enough stock
        } else {
            if ($quantity <= $product->stock) {
                $cart->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);

                return true;
            }

            return false;
        }
    }

    /**
     * Update exact quantity of an item.
     */
    public function updateQuantity($productId, $action)
    {
        $cart = $this->getCart();
        $cartItem = $cart->items()->where('product_id', $productId)->first();

        if (! $cartItem) {
            return;
        }

        $product = $cartItem->product;

        if ($action === 'increment') {
            if ($cartItem->quantity < $product->stock) {
                $cartItem->increment('quantity');

                return true;
            }

            return false; // Limit reached
        } elseif ($action === 'decrement') {
            if ($cartItem->quantity > 1) {
                $cartItem->decrement('quantity');

                return true;
            } else {
                $cartItem->delete();

                return true;
            }
        }

        return false;
    }

    /**
     * Set exact quantity of an item directly.
     */
    public function setQuantity($productId, $quantity)
    {
        $cart = $this->getCart();
        $cartItem = $cart->items()->where('product_id', $productId)->first();

        if (! $cartItem) {
            return false;
        }

        $product = $cartItem->product;
        $qty = max(1, min((int) $quantity, $product->stock));

        $cartItem->update(['quantity' => $qty]);

        return true;
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem($productId)
    {
        $cart = $this->getCart();
        $cart->items()->where('product_id', $productId)->delete();
    }

    /**
     * Clear the entire cart.
     */
    public function clear()
    {
        $cart = $this->getCart();
        $cart->items()->delete();
    }

    /**
     * Merge guest cart into user cart upon login.
     */
    public function mergeGuestCartIntoUserCart($user, $sessionId = null)
    {
        $sessionId = $sessionId ?: Session::getId();
        $guestCart = Cart::with('items.product')->where('session_id', $sessionId)->whereNull('user_id')->first();

        if (! $guestCart || $guestCart->items->isEmpty()) {
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($guestCart, $user) {
            $userCart = Cart::firstOrCreate(['user_id' => $user->id]);
            $userCartItems = $userCart->items->keyBy('product_id');

            $mergedData = [];
            $now = now();

            // Merge guest items into user items
            foreach ($guestCart->items as $guestItem) {
                $productId = $guestItem->product_id;
                $maxStock = $guestItem->product ? $guestItem->product->stock : 0;

                if ($userCartItems->has($productId)) {
                    $newQuantity = min($userCartItems[$productId]->quantity + $guestItem->quantity, $maxStock);
                    $mergedData[$productId] = [
                        'cart_id' => $userCart->id,
                        'product_id' => $productId,
                        'quantity' => $newQuantity,
                        'created_at' => $userCartItems[$productId]->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $now->format('Y-m-d H:i:s'),
                    ];
                    $userCartItems->forget($productId);
                } else {
                    $mergedData[$productId] = [
                        'cart_id' => $userCart->id,
                        'product_id' => $productId,
                        'quantity' => min($guestItem->quantity, $maxStock),
                        'created_at' => $now->format('Y-m-d H:i:s'),
                        'updated_at' => $now->format('Y-m-d H:i:s'),
                    ];
                }
            }

            // Keep the rest of the user's cart items
            foreach ($userCartItems as $productId => $userItem) {
                $mergedData[$productId] = [
                    'cart_id' => $userCart->id,
                    'product_id' => $productId,
                    'quantity' => $userItem->quantity,
                    'created_at' => $userItem->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $userItem->updated_at->format('Y-m-d H:i:s'),
                ];
            }

            // Bulk replace to avoid N+1 updates
            $userCart->items()->delete();
            \App\Models\CartItem::insert(array_values($mergedData));
            $guestCart->delete();
        });
    }
}
