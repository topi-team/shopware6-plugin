<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\EventProcessing;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use TopiPaymentIntegration\Event\EventInterface;
use TopiPaymentIntegration\Event\OrderEvent;

class SetOrderIdProcessor implements ProcessorInterface
{
    /**
     * @param EntityRepository<OrderTransactionCollection> $orderTransactionRepository
     * @param EntityRepository<OrderCollection>            $orderRepository
     */
    public function __construct(
        private readonly EntityRepository $orderTransactionRepository,
        private readonly EntityRepository $orderRepository,
    ) {
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

        $orderTransaction = $this->orderTransactionRepository->search(
            new Criteria([$event->order->sellerOfferReference]),
            $context
        )->first();

        if (!$orderTransaction instanceof OrderTransactionEntity) {
            return;
        }

        $this->orderRepository->update([[
            'id' => $orderTransaction->getOrderId(),
            'customFields' => ['topiOrderId' => $event->order->id],
        ]], $context);
    }
}
