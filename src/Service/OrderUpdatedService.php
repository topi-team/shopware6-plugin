<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use TopiPaymentIntegration\ApiClient\Client;
use TopiPaymentIntegration\ApiClient\Exception\TopiApiException;
use TopiPaymentIntegration\ApiClient\Factory\EnvironmentFactory;
use TopiPaymentIntegration\ApiClient\Order\SetOrderMetadataData;

readonly class OrderUpdatedService
{
    private const TRACKING_URL_MAP = [
        'UPS' => 'https://www.ups.com/track?loc=de_DE&Requester=DAN&tracknum=%s',
        'DHL' => 'https://nolp.dhl.de/nextt-online-public/set_identcodes.do?lang=de&idc=%s',
        'DPD' => 'https://tracking.dpd.de/status/de_DE/parcel/%s',
    ];

    /**
     * @param EntityRepository<OrderCollection> $orderRepository
     */
    public function __construct(
        private EntityRepository $orderRepository,
        private Client $client,
        private EnvironmentFactory $environmentFactory,
    ) {
    }

    public function orderUpdated(string $orderId, array $trackingCodes, Context $context): void
    {
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search(new Criteria([$orderId]), $context)->first();

        if (is_null($order)) {
            return;
        }

        $customFields = $order->getCustomFields();
        if (!isset($customFields['topi_order_id']) || !$customFields['topi_order_id']) {
            return;
        }

        $topiOrderId = $customFields['topi_order_id'];

        $orderMetadata = new SetOrderMetadataData();
        $orderMetadata->orderId = $topiOrderId;
        $orderMetadata->metadata['tracking_numbers'] = implode(';', $trackingCodes);

        $trackingUrls = $this->generateTrackingUrls($trackingCodes);
        if (!empty($trackingUrls)) {
            $orderMetadata->metadata['tracking_urls'] = $trackingUrls;
        }

        try {
            $this->client->order(
                $this->environmentFactory->makeEnvironment($order->getSalesChannelId())
            )->setOrderMetadata($orderMetadata);
        } catch (TopiApiException) {
            // exception is logged, catch it here so no error is displayed to the user / API application
        }
    }

    private function generateTrackingUrls(array $trackingCodes): array
    {
        $urls = [];
        $carriers = array_keys(self::TRACKING_URL_MAP);
        foreach ($trackingCodes as $code) {
            if (!str_contains($code, ':')) {
                continue;
            }

            [$carrier, $splitCode] = explode(':', $code);
            if (in_array($carrier, $carriers, true)) {
                $urls[] = sprintf(self::TRACKING_URL_MAP[$carrier], $splitCode);
            }
        }

        return $urls;
    }
}
