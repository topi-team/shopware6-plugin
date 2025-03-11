<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Subscriber;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TopiPaymentIntegration\ApiClient\Client;
use TopiPaymentIntegration\ApiClient\Order\SetOrderMetadataData;
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
            $orderId = $orderDeliveryData['orderId'];
            $trackingCodes = $orderDeliveryData['trackingCodes'];

            $this->orderUpdatedService->orderUpdated($orderId, $trackingCodes, $entityWrittenEvent->getContext());
        }
    }
}
