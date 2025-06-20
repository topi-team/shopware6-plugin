<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\EventProcessing;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use TopiPaymentIntegration\Event\EventInterface;
use TopiPaymentIntegration\Event\OfferEvent;

class UpdateOrderStatusFromAcceptedOfferProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderTransactionStateHandler $transactionStateHandler,
    ) {
    }

    public function canProcess(string $event): bool
    {
        return 'offer.accepted' === $event;
    }

    public function process(EventInterface $event, Context $context): void
    {
        if (!$event instanceof OfferEvent) {
            return;
        }

        $this->transactionStateHandler->paid($event->offer->sellerOfferReference, $context);
    }
}
