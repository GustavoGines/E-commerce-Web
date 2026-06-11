<?php

namespace App\Listeners;

use App\Events\PaymentApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateOrderOnPayment implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentApproved $event): void
    {
        $payment = $event->payment;
        $dataId = $event->dataId;

        DB::transaction(function () use ($payment, $dataId) {
            $orderId = $payment->external_reference ?? null;
            if (! $orderId) {
                Log::warning('Webhook MP: pago sin external_reference', ['payment_id' => $dataId]);
                return;
            }

            $order = Order::find($orderId);
            if (! $order) {
                Log::warning('Webhook MP: orden no encontrada', ['order_id' => $orderId]);
                return;
            }

            // Guardar el ID del pago en la orden
            $order->mp_payment_id = $dataId;

            // Mapear el estado de MP a nuestros estados internos
            $order->status = match ($payment->status) {
                'approved' => 'pagado',
                'pending',
                'in_process' => 'pendiente',
                'rejected',
                'cancelled' => 'cancelado',
                default => $order->status, // no cambiar si es estado desconocido
            };

            $order->save();
            
            Log::info('Evento: orden actualizada', [
                'order_id' => $orderId,
                'payment_id' => $dataId,
                'new_status' => $order->status,
            ]);
        });
    }
}
