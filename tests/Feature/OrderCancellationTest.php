<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_pending_order_restores_stock()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 50]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendiente',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 100,
        ]);

        $this->actingAs($user);

        Volt::test('my-orders')
            ->call('cancelarOrden', $order->id);

        $this->assertEquals('cancelado', $order->fresh()->status);
        $this->assertEquals(55, $product->fresh()->stock, 'Stock should be restored from 50 to 55');
    }

    public function test_deleting_pending_order_restores_stock()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 50]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendiente',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 100,
        ]);

        $this->actingAs($user);

        Volt::test('my-orders')
            ->call('eliminarOrden', $order->id);

        $this->assertNull(Order::find($order->id));
        $this->assertEquals(55, $product->fresh()->stock, 'Stock should be restored from 50 to 55 when deleting pending order');
    }

    public function test_admin_deleting_paid_order_restores_stock()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['stock' => 10]);

        $order = Order::factory()->create(['status' => 'pagado']);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 100,
        ]);

        $this->actingAs($admin);

        Volt::test('admin.manage-orders')
            ->call('deleteOrder', $order->id);

        $this->assertNull(Order::find($order->id));
        $this->assertEquals(13, $product->fresh()->stock, 'Stock should be restored from 10 to 13 when admin deletes paid order');
    }
}
