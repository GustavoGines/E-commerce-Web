<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserWholesaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_not_wholesale_by_default()
    {
        $user = User::factory()->create();
        $this->assertFalse($user->isWholesaleCustomer());
    }

    public function test_user_is_not_wholesale_if_orders_are_pending_or_cancelled()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Orden pendiente
        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendiente',
        ]);
        $order1->items()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'price' => 100,
        ]);

        $this->assertFalse($user->isWholesaleCustomer());

        // Orden cancelada
        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelado',
        ]);
        $order2->items()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'price' => 100,
        ]);

        // Asegurarse de limpiar la caché del usuario si fuera necesario, 
        // aunque son instancias nuevas en memoria.
        $this->assertFalse($user->fresh()->isWholesaleCustomer());
    }

    public function test_user_is_wholesale_if_has_paid_order_with_10_or_more_items()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Orden pagada con cantidad >= 10
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pagado',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'price' => 100,
        ]);

        $this->assertTrue($user->isWholesaleCustomer());
    }
    
    public function test_user_is_wholesale_if_has_completed_order_with_10_or_more_items()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Orden completada con cantidad >= 10
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completado',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 15,
            'price' => 100,
        ]);

        $this->assertTrue($user->isWholesaleCustomer());
    }
}
