<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\ShippingMethod;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use TopiPaymentIntegration\ApiClient\Exception\TopiApiException;

readonly class ShippingMethodClient
{
    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
    ) {
    }

    /** @param array<mixed> $options additional cURL options */
    public function list(array $options = []): \Generator
    {
        $responseData = $this->client->request('GET', 'shipping-method', array_merge([
            'query' => [
                'page' => 0,
            ],
        ], $options))->toArray();

        yield from $responseData['data'];
        if ($responseData['pagination']['has_more']) {
            yield from $this->list(['query' => ['page' => $responseData['pagination']['page'] + 1]]);
        }
    }

    /** @param array<mixed> $options */
    public function create(ShippingMethod $shippingMethod, array $options = []): void
    {
        try {
            $response = $this->client->request('POST', 'shipping-method/method', array_merge([
                'json' => $shippingMethod,
            ], $options));

            // allow 422 error responses which are caused by duplicate entries
            if (422 === $response->getStatusCode()) {
                return;
            }

            $this->checkStatusCode($response);
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error($e);
            throw new TopiApiException($e->getMessage(), $e->getResponse()?->getStatusCode() ?? $e->getCode(), $e);
        }
    }

    /**
     * @see \Symfony\Component\HttpClient\Response\CommonResponseTrait::checkStatusCode()
     *
     * @throws TransportExceptionInterface
     */
    private function checkStatusCode(ResponseInterface $response): void
    {
        if (500 <= $response->getStatusCode()) {
            throw new ServerException($response);
        }

        if (400 <= $response->getStatusCode()) {
            throw new ClientException($response);
        }

        if (300 <= $response->getStatusCode()) {
            throw new RedirectionException($response);
        }
    }
}
