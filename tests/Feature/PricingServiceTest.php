<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_buying_small_quantity_pays_retail_price()
    {
        $product = Product::factory()->create([
            'retail_price' => 1000,
            'wholesale_price' => 800,
            'wholesale_min_quantity' => 10,
        ]);

        $service = new PricingService();

        // 1. Invitado
        $price = $service->unitPrice($product, 5, null);
        $this->assertEquals(1000, $price);

        // 2. Usuario regular (sin compras anteriores)
        $user = User::factory()->create();
        $price = $service->unitPrice($product, 5, $user);
        $this->assertEquals(1000, $price);
    }

    public function test_any_user_buying_minimum_quantity_pays_wholesale_price()
    {
        $product = Product::factory()->create([
            'retail_price' => 1000,
            'wholesale_price' => 800,
            'wholesale_min_quantity' => 10,
        ]);

        $service = new PricingService();

        // Invitado compra 10
        $price = $service->unitPrice($product, 10, null);
        $this->assertEquals(800, $price);

        // Invitado compra 15
        $price = $service->unitPrice($product, 15, null);
        $this->assertEquals(800, $price);
    }

    public function test_wholesale_customer_always_pays_wholesale_price_regardless_of_quantity()
    {
        $product = Product::factory()->create([
            'retail_price' => 1000,
            'wholesale_price' => 800,
            'wholesale_min_quantity' => 10,
        ]);

        $user = User::factory()->create();
        // Crear una orden previa para que sea VIP
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pagado',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 10, // Compra previa de 10
            'price' => 800,
        ]);

        $service = new PricingService();

        // Usuario VIP compra solo 1 unidad
        $price = $service->unitPrice($product, 1, $user);
        
        $this->assertEquals(800, $price, 'Wholesale customer should get wholesale price even for 1 unit');
    }
}
