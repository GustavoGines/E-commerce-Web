<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Recibe las notificaciones IPN/Webhook de MercadoPago.
     * MercadoPago envía un POST con el tipo de notificación y el ID del recurso.
     * Siempre debemos responder 200 inmediatamente, y luego procesar de forma idempotente.
     */
    public function handle(Request $request, MercadoPagoService $mpService): \Illuminate\Http\JsonResponse
    {
        // SEC-01 FIX: Validate the HMAC x-signature header before processing.
        // This ensures the request genuinely comes from MercadoPago.
        if (!$this->isValidSignature($request)) {
            Log::warning('MercadoPago Webhook: Firma inválida rechazada', [
                'ip'        => $request->ip(),
                'x-sig'     => $request->header('x-signature'),
                'x-req-id'  => $request->header('x-request-id'),
            ]);
            // Return 200 to avoid MP retrying, but do NOT process the payload.
            return response()->json(['status' => 'invalid_signature'], 200);
        }

        // Loggear el payload completo para debugging
        Log::info('MercadoPago Webhook recibido', $request->all());

        $type   = $request->input('type');
        $dataId = $request->input('data.id'); // ID del pago

        // Solo nos interesan las notificaciones de tipo "payment"
        if ($type !== 'payment' || empty($dataId)) {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            // Consultamos el pago directamente a la API de MP (nunca confiar solo en el webhook)
            $payment = $mpService->getPayment($dataId);

            // Disparamos el evento para que la lógica de negocio se maneje en listeners separados
            event(new \App\Events\PaymentApproved($payment, $dataId));

        } catch (\Exception $e) {
            // Siempre devolver 200 para que MP no reintente indefinidamente.
            // El error queda loggeado para revisión manual.
            Log::error('Webhook MP: excepción al procesar pago', [
                'payment_id' => $dataId,
                'error'      => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * SEC-01: Validates the HMAC-SHA256 signature sent by MercadoPago.
     *
     * MercadoPago sends the signature in the `x-signature` header with the format:
     *   ts=<timestamp>,v1=<hash>
     *
     * The signed manifest is: "id:<data.id>;request-id:<x-request-id>;ts:<ts>;"
     *
     * @see https://www.mercadopago.com.ar/developers/en/docs/your-integrations/notifications/webhooks
     */
    private function isValidSignature(Request $request): bool
    {
        $secret = config('services.mercadopago.webhook_secret');

        // If no secret is configured (e.g. local dev without MP_WEBHOOK_SECRET in .env),
        // skip validation in non-production environments to avoid blocking development.
        if (empty($secret)) {
            if (!app()->isProduction()) {
                Log::warning('Webhook MP: MP_WEBHOOK_SECRET not set — skipping signature validation (dev only).');
                return true;
            }
            // In production, always require the secret.
            Log::error('Webhook MP: MP_WEBHOOK_SECRET is not configured in production!');
            return false;
        }

        $xSignature = $request->header('x-signature', '');
        $xRequestId = $request->header('x-request-id', '');
        $dataId     = $request->input('data.id', '');

        // Parse ts and v1 from the x-signature header
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $parts[trim($key)] = trim($value);
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        // Build the signed manifest string
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$parts['ts']};";

        // Compute the expected HMAC-SHA256
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $parts['v1']);
    }
}
