<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\EventProcessing;

use Shopware\Bundle\AttributeBundle\Service\DataPersisterInterface;
use Shopware\Core\Framework\Context;
use TopiPaymentIntegration\Event\EventInterface;
use TopiPaymentIntegration\Event\OrderEvent;

class SetOrderIdProcessor implements ProcessorInterface
{
    private DataPersisterInterface $dataPersister;

    public function __construct(DataPersisterInterface $dataPersister)
    {
        $this->dataPersister = $dataPersister;
    }

    public function canProcess(string $event): bool
    {
        return 'order.created' === $event;
    }

    public function process(EventInterface $event, Context $context): void
    {
        if (!$event instanceof OrderEvent) {
            return;
        }

        // $event->order->offerId,
        // $event->order->sellerOfferReference,

        // write order id $event->order->id to shopware order custom field
    }
}
