<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_invalid_signature()
    {
        // Forzar un secret en config
        Config::set('services.mercadopago.webhook_secret', 'test-secret');

        $response = $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '123456'],
        ], [
            'x-signature' => 'ts=123,v1=wronghash',
            'x-request-id' => 'req-123',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'invalid_signature']);
    }

    public function test_webhook_accepts_valid_signature()
    {
        Config::set('services.mercadopago.webhook_secret', 'test-secret');

        $dataId = '123456';
        $requestId = 'req-123';
        $ts = time();
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $hash = hash_hmac('sha256', $manifest, 'test-secret');
        $signatureHeader = "ts={$ts},v1={$hash}";

        // Evitar que el servicio MP real haga requests
        $this->mock(\App\Services\MercadoPagoService::class, function ($mock) use ($dataId) {
            $mock->shouldReceive('getPayment')->with($dataId)->andReturn((object)['status' => 'approved', 'external_reference' => '999']);
        });

        $response = $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => $dataId],
        ], [
            'x-signature' => $signatureHeader,
            'x-request-id' => $requestId,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);
    }
}
