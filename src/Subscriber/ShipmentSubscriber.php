<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Subscriber;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TopiPaymentIntegration\Service\OrderUpdatedService;

readonly class ShipmentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_DELIVERY_WRITTEN_EVENT => 'captureTrackingNumber',
            PreWriteValidationEvent::class => 'triggerChangeSet',
        ];
    }

    public function __construct(
        private OrderUpdatedService $orderUpdatedService,
        private EntityRepository $orderDeliveryRepository,
    ) {
    }

    public function triggerChangeSet(PreWriteValidationEvent $event): void
    {
        if (Defaults::LIVE_VERSION !== $event->getContext()->getVersionId()) {
            return;
        }

        foreach ($event->getCommands() as $command) {
            if (!$command instanceof ChangeSetAware) {
                continue;
            }

            if (OrderDeliveryDefinition::ENTITY_NAME !== $command->getEntityName()) {
                continue;
            }

            $command->requestChangeSet();
        }
    }

    public function captureTrackingNumber(EntityWrittenEvent $entityWrittenEvent): void
    {
        $payload = $entityWrittenEvent->getPayloads();

        foreach ($payload as $orderDeliveryData) {
            if (!isset($orderDeliveryData['trackingCodes'])) {
                continue;
            }

            $orderId = $orderDeliveryData['orderId'] ?? null;
            if (is_null($orderId)) {
                /** @var OrderDeliveryEntity $orderDelivery */
                $orderDelivery = $this->orderDeliveryRepository->search(new Criteria([$payload['id']]), $entityWrittenEvent->getContext())
                    ->getEntities()
                    ->first();

                $orderId = $orderDelivery->getOrderId();
            }

            $trackingCodes = $orderDeliveryData['trackingCodes'];

            $this->orderUpdatedService->orderUpdated($orderId, $trackingCodes, $entityWrittenEvent->getContext());
        }
    }
}
