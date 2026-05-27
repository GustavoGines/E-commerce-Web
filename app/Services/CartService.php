<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
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

        if (!$product) {
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
                    'quantity' => $quantity
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

        if (!$cartItem) {
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
    public function mergeGuestCartIntoUserCart($user)
    {
        $sessionId = Session::getId();
        $guestCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->first();

        if (!$guestCart || $guestCart->items()->count() === 0) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $item) {
            $userCartItem = $userCart->items()->where('product_id', $item->product_id)->first();

            if ($userCartItem) {
                // If the user already has this product, increment the quantity (cap by stock)
                $newQuantity = $userCartItem->quantity + $item->quantity;
                $maxStock = $item->product->stock;
                $userCartItem->update([
                    'quantity' => min($newQuantity, $maxStock)
                ]);
            } else {
                // Move item to user cart
                $item->update(['cart_id' => $userCart->id]);
            }
        }

        // Delete the guest cart after merging
        $guestCart->delete();
    }
}
