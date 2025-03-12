<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Event;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;
use TopiPaymentIntegration\ApiClient\Order\Order;

class OrderEvent implements EventInterface
{
    use AppliesArrayDataTrait;

    public Order $order;

    /**
     * @var 'order.*'|'order.created'|'order.partially_fulfilled'|'order.completed'|'order.canceled'|'order.rejected'
     */
    public string $event = 'order.*';

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): void
    {
        $this->event = $event;
    }
}
