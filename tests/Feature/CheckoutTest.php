<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_redirects_if_cart_is_empty()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);

        $component = Volt::test('checkout');

        $component->assertRedirect(route('home', absolute: false));
    }

    public function test_checkout_loads_cart_and_calculates_subtotal()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $product = Product::factory()->create(['stock' => 10, 'retail_price' => 100]);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 3);

        $component = Volt::test('checkout');

        $component->assertSet('subtotal', 300)
                  ->assertSee($product->name);
    }

    public function test_checkout_validates_required_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $product = Product::factory()->create(['stock' => 10, 'retail_price' => 100]);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 1);

        // Forzamos un tema que requiera los datos de envío
        Volt::test('checkout', ['theme' => 'stealth'])
            ->call('placeOrder')
            ->assertHasErrors(['phone', 'address_street', 'address_number', 'city', 'state', 'zip_code']);
    }

    public function test_checkout_creates_order_and_decrements_stock()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $product1 = Product::factory()->create(['stock' => 10, 'retail_price' => 100]);
        $product2 = Product::factory()->create(['stock' => 5, 'retail_price' => 50]);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product1->id, 2);
        $cartService->addItem($product2->id, 1);

        // Evitar llamar a MercadoPago real
        $this->mock(MercadoPagoService::class, function ($mock) {
            $mock->shouldReceive('createPreference')->andReturn([
                'preference_id' => 'mock-preference-id',
                'init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=mock',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=mock',
            ]);
        });

        Volt::test('checkout', ['theme' => 'stealth'])
            ->set('phone', '12345678')
            ->set('address_street', 'Fake St')
            ->set('address_number', '123')
            ->set('city', 'Springfield')
            ->set('state', 'IL')
            ->set('zip_code', '12345')
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertRedirect(); // Debería redirigir a MP o mostrar preferencia

        // Validar que el stock se descontó atómicamente
        $this->assertEquals(8, $product1->fresh()->stock);
        $this->assertEquals(4, $product2->fresh()->stock);

        // Validar que se creó la orden
        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(250, $order->total); // 2*100 + 1*50
        $this->assertEquals('pendiente', $order->status);

        // Validar que se limpió el carrito de la sesión
        $this->assertEmpty(session('cart', []));
    }
}
