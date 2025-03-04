<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\ShippingMethod;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class ShippingMethodClient
{
    public function __construct(
        private HttpClientInterface $client,
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
        $this->client->request('POST', 'shipping-method/method', array_merge([
            'json' => $shippingMethod,
        ], $options));
    }
}
