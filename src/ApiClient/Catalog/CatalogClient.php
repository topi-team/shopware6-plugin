<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TopiPaymentIntegration\ApiClient\Common\ProductReferenceCollection;
use TopiPaymentIntegration\ApiClient\Common\ProductSummary;
use TopiPaymentIntegration\ApiClient\Exception\TopiApiException;
use TopiPaymentIntegration\ApiClient\PreProcessOptionsTrait;

class CatalogClient
{
    use PreProcessOptionsTrait;

    /** @var array<string, array<string, mixed>> */
    private array $responseCache = [];

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @param array<mixed> $options */
    public function importCatalog(ProductBatch $productBatch, array $options = []): void
    {
        $jsonData = [
            'products' => $productBatch->getProducts(),
        ];

        $this->client->request('POST', 'catalog/import', array_merge([
            'json' => $jsonData,
        ], $options));
    }

    /**
     * @param array<mixed> $options
     *
     * @return ProductSummary[]
     *
     * @throws \JsonException
     */
    public function checkSupported(ProductReferenceCollection $productReferences, array $options = []): array
    {
        $jsonData = [
            'seller_product_references' => $productReferences->getProductReferences(),
        ];

        $requestOptions = array_merge([
            'json' => $jsonData,
        ], $options);

        if (!isset($this->responseCache[__METHOD__])) {
            $this->responseCache[__METHOD__] = [];
        }

        $cacheKey = md5(serialize($requestOptions));
        $dontUseCache = isset($options['cache']) && false === $options['cache'];
        if (array_key_exists($cacheKey, $this->responseCache[__METHOD__]) && !$dontUseCache) {
            return $this->responseCache[__METHOD__][$cacheKey];
        }

        try {
            $response = $this->client->request('POST', 'catalog/check-supported',
                $this->preProcessOptions($requestOptions));
            $responseData = $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error($e);
            throw new TopiApiException($e->getMessage(), $e->getResponse()?->getStatusCode() ?? $e->getCode(), $e);
        }

        $result = [];
        foreach ($responseData['products'] as $productSummaryData) {
            $item = new ProductSummary();
            $item->applyData($productSummaryData);

            $result[] = $item;
        }

        $this->responseCache[__METHOD__][$cacheKey] = $result;

        return $result;
    }

    /** @param array<mixed> $options */
    public function calculatePricing(PricingRequest $pricingRequest, array $options = []): CalculatePricingResponse
    {
        $jsonData = [
            'pricing_request' => $pricingRequest,
        ];

        try {
            $response = $this->client->request('POST', 'catalog/pricing', $this->preProcessOptions(array_merge([
                'json' => $jsonData,
            ], $options)));

            $responseData = $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error($e);
            throw new TopiApiException($e->getMessage(), $e->getResponse()?->getStatusCode() ?? $e->getCode(), $e);
        }

        $result = new CalculatePricingResponse();
        $result->applyData($responseData);

        return $result;
    }
}
