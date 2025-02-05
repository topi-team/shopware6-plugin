<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Order;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\LoggerInterface;
use TopiPaymentIntegration\ApiClient\PreProcessOptionsTrait;

readonly class OrderClient
{
    use PreProcessOptionsTrait;

    public function __construct(
        private GuzzleClient $client,
        private LoggerInterface $logger,
    ) {
    }

    /** @param array<mixed> $options */
    public function setOrderMetadata(SetOrderMetadataData $data, array $options = []): Order
    {
        $start = microtime(true);
        $response = $this->client->patch(sprintf('orders/%s', $data->orderId), $this->preProcessOptions(array_merge([
            'json' => [
                'metadata' => $data->metadata,
            ],
        ], $options)));

        $responseData = (string) $response->getBody();

        $time_elapsed_secs = microtime(true) - $start;
        $this->logger->debug('Topi API took: '.$time_elapsed_secs.'s');
        $this->logger->debug('Response: '.$response->getStatusCode().' Data: '.$responseData);

        $order = new Order();
        $order->applyData(
            @json_decode(
                $responseData,
                true,
                512,
                JSON_THROW_ON_ERROR
            )
        );

        return $order;
    }
}
