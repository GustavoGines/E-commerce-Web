<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentApproved
{
    use Dispatchable, SerializesModels;

    public $payment;
    public $dataId;

    /**
     * Create a new event instance.
     */
    public function __construct($payment, $dataId)
    {
        $this->payment = $payment;
        $this->dataId  = $dataId;
    }
}
