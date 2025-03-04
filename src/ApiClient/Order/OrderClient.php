<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Order;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TopiPaymentIntegration\ApiClient\PreProcessOptionsTrait;

readonly class OrderClient
{
    use PreProcessOptionsTrait;

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
    ) {
    }

    /** @param array<mixed> $options */
    public function setOrderMetadata(SetOrderMetadataData $data, array $options = []): Order
    {
        $start = microtime(true);
        $response = $this->client->request('PATCH', sprintf('orders/%s', $data->orderId), $this->preProcessOptions(array_merge([
            'json' => [
                'metadata' => $data->metadata,
            ],
        ], $options)));

        $order = new Order();
        $order->applyData($response->toArray());

        return $order;
    }
}
