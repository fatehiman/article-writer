<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Publishing\DeliveryDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $deliveryId)
    {
    }

    public function handle(DeliveryDispatcher $dispatcher): void
    {
        $delivery = Delivery::query()->find($this->deliveryId);
        if ($delivery) {
            $dispatcher->send($delivery);
        }
    }
}
