<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TopiPaymentIntegration\Config\ConfigValue;
use TopiPaymentIntegration\Config\PluginConfigService;
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

    /**
     * @param EntityRepository<OrderDeliveryCollection> $orderDeliveryRepository
     */
    public function __construct(
        private OrderUpdatedService $orderUpdatedService,
        private EntityRepository $orderDeliveryRepository,
        private LoggerInterface $logger,
        private PluginConfigService $config,
    ) {
    }

    public function triggerChangeSet(PreWriteValidationEvent $event): void
    {
        if (!$this->config->getBool(ConfigValue::ENABLE_TRACKING_CODE_SYNC)) {
            return;
        }

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
        if (!$this->config->getBool(ConfigValue::ENABLE_TRACKING_CODE_SYNC)) {
            return;
        }

        try {
            $payloads = $entityWrittenEvent->getPayloads();
            $orderIdsByDeliveryId = $this->loadMissingOrderIds($payloads, $entityWrittenEvent->getContext());

            foreach ($payloads as $orderDeliveryData) {
                if (!isset($orderDeliveryData['trackingCodes'])) {
                    continue;
                }

                $orderId = $orderDeliveryData['orderId'] ?? $orderIdsByDeliveryId[$orderDeliveryData['id'] ?? ''] ?? null;
                if (is_null($orderId)) {
                    continue;
                }

                $trackingCodes = $orderDeliveryData['trackingCodes'];

                $this->orderUpdatedService->orderUpdated($orderId, $trackingCodes, $entityWrittenEvent->getContext());
            }
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send tracking codes to topi: '.$e->getMessage());

            return;
        }
    }

    /**
     * Resolves the order ids of all written deliveries with tracking codes whose payload does not contain one.
     *
     * @param array<array<string, mixed>> $payloads
     *
     * @return array<string, string> order ids keyed by order delivery id
     */
    private function loadMissingOrderIds(array $payloads, Context $context): array
    {
        $deliveryIds = [];
        foreach ($payloads as $orderDeliveryData) {
            if (isset($orderDeliveryData['trackingCodes'], $orderDeliveryData['id']) && !isset($orderDeliveryData['orderId'])) {
                $deliveryIds[] = $orderDeliveryData['id'];
            }
        }

        if ([] === $deliveryIds) {
            return [];
        }

        $orderDeliveries = $this->orderDeliveryRepository->search(new Criteria($deliveryIds), $context)->getEntities();

        $orderIds = [];
        foreach ($orderDeliveries as $orderDelivery) {
            $orderIds[$orderDelivery->getId()] = $orderDelivery->getOrderId();
        }

        return $orderIds;
    }
}
