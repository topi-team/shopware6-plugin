<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\ShippingMethod;

use GuzzleHttp\Client as GuzzleClient;

readonly class ShippingMethodClient
{
    public function __construct(
        private GuzzleClient $client,
    ) {
    }

    /**
     * @param array<mixed> $options additional cURL options
     *
     * @throws \JsonException
     */
    public function list(array $options = []): \Generator
    {
        $responseString = (string) $this->client->get('shipping-method', array_merge([
            'query' => [
                'page' => 0,
            ],
        ], $options))->getBody();

        $responseData = json_decode(
            $responseString,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        yield from $responseData['data'];
        if ($responseData['pagination']['has_more']) {
            yield from $this->list(['query' => ['page' => $responseData['pagination']['page'] + 1]]);
        }
    }

    /** @param array<mixed> $options */
    public function create(ShippingMethod $shippingMethod, array $options = []): void
    {
        $this->client->post('shipping-method/method', array_merge([
            'json' => $shippingMethod,
        ], $options));
    }
}
