<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Order;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TopiPaymentIntegration\ApiClient\Exception\TopiApiException;
use TopiPaymentIntegration\ApiClient\PreProcessOptionsTrait;

readonly class OrderClient
{
    use PreProcessOptionsTrait;

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<mixed> $options
     */
    public function setOrderMetadata(SetOrderMetadataData $data, array $options = []): Order
    {
        try {
            $response = $this->client->request('PATCH', sprintf('orders/%s', $data->orderId),
                $this->preProcessOptions(array_merge([
                    'json' => [
                        'metadata' => $data->metadata,
                    ],
                ], $options)));

            $order = new Order();
            $order->applyData($response->toArray());

            return $order;
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error($e);
            throw new TopiApiException($e->getMessage(), $e instanceof HttpExceptionInterface ? $e->getResponse()->getStatusCode() : $e->getCode(), $e);
        }
    }
}
