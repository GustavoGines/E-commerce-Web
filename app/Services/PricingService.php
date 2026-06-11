<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

/**
 * DRY-01: Servicio centralizado de cálculo de precios.
 *
 * Antes de este servicio, la lógica de precio estaba triplicada en:
 * - cart-panel.blade.php
 * - checkout.blade.php
 * - MercadoPagoService.php
 *
 * Ahora hay una única fuente de verdad. Si la regla de negocio cambia,
 * se modifica SOLO aquí.
 */
class PricingService
{
    /**
     * Calcula el precio unitario de un producto para un cliente dado.
     *
     * Reglas (en orden de prioridad):
     * 1. Si el usuario es cliente mayorista VIP → precio mayorista en TODO.
     * 2. Si compra la cantidad mínima del producto → precio mayorista.
     * 3. En cualquier otro caso → precio minorista.
     *
     * @param  Product        $product   Producto a calcular.
     * @param  int            $quantity  Cantidad solicitada.
     * @param  \App\Models\User|null  $user  Usuario autenticado (null = invitado).
     * @return float          Precio unitario a aplicar.
     */
    public function unitPrice(Product $product, int $quantity, $user = null): float
    {
        // Regla 1: Cliente VIP — mayorista en todo el carrito
        if ($user && $user->isWholesaleCustomer()) {
            return (float) $product->wholesale_price;
        }

        // Regla 2: Cantidad mínima alcanzada para este producto
        if ($quantity >= $product->wholesale_min_quantity) {
            return (float) $product->wholesale_price;
        }

        // Regla 3: Precio de lista
        return (float) $product->retail_price;
    }
}
