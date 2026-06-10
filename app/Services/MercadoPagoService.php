<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment;

class MercadoPagoService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
    }

    /**
     * Crea una preferencia de pago en MercadoPago para una orden dada.
     * Devuelve la URL de pago (init_point) y el preference_id.
     *
     * @param  Order  $order  La orden ya persistida en DB.
     * @param  array  $cartItems  Array de [product_id => quantity].
     * @return array{preference_id: string, init_point: string, sandbox_init_point: string}
     */
    public function createPreference(Order $order, array $cartItems): array
    {
        $productIds = array_keys($cartItems);
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Construir los items de la preferencia
        $items = [];
        foreach ($cartItems as $productId => $quantity) {
            if (! isset($products[$productId])) {
                continue;
            }

            $product = $products[$productId];
            $unitPrice = ($quantity >= $product->wholesale_min_quantity) ? (float) $product->wholesale_price : (float) $product->retail_price;

            $items[] = [
                'id' => (string) $product->id,
                'title' => $product->name,
                'quantity' => (int) $quantity,
                'unit_price' => $unitPrice,
                'currency_id' => 'ARS',
            ];
        }

        // URLs de retorno
        $backUrls = [
            'success' => route('checkout.success', ['order' => $order->id]),
            'failure' => route('checkout.failure', ['order' => $order->id]),
            'pending' => route('checkout.pending', ['order' => $order->id]),
        ];

        // Datos del comprador
        $payer = [
            'name' => $order->user->name ?? '',
            'email' => $order->user->email ?? '',
        ];

        // Metadata para identificar la orden en el webhook
        $metadata = [
            'order_id' => $order->id,
        ];

        $requestData = [
            'items' => $items,
            'payer' => $payer,
            'back_urls' => $backUrls,
            'external_reference' => (string) $order->id,
            'metadata' => $metadata,
            'statement_descriptor' => config('app.name'),
        ];

        // auto_return y notification_url requieren URLs públicas
        // Se activan en producción O cuando hay un túnel activo (desarrollo con localtunnel/ngrok)
        if (app()->isProduction() || env('TUNNEL_ACTIVE')) {
            $requestData['auto_return'] = 'approved';
            $requestData['notification_url'] = route('webhook.mercadopago');
        }

        try {
            $client = new PreferenceClient;
            $preference = $client->create($requestData);

            return [
                'preference_id' => $preference->id,
                'init_point' => $preference->init_point,
                'sandbox_init_point' => $preference->sandbox_init_point,
            ];
        } catch (MPApiException $e) {
            Log::error('MercadoPago API Error al crear preferencia', [
                'order_id' => $order->id,
                'status' => $e->getApiResponse()->getStatusCode(),
                'content' => $e->getApiResponse()->getContent(),
            ]);
            throw $e;
        }
    }

    /**
     * Consulta un pago individual por su ID (útil en el webhook).
     *
     * @return Payment
     */
    public function getPayment(int|string $paymentId)
    {
        try {
            $client = new PaymentClient;

            return $client->get((int) $paymentId);
        } catch (MPApiException $e) {
            Log::error('MercadoPago API Error al consultar pago', [
                'payment_id' => $paymentId,
                'status' => $e->getApiResponse()->getStatusCode(),
                'content' => $e->getApiResponse()->getContent(),
            ]);
            throw $e;
        }
    }
}
