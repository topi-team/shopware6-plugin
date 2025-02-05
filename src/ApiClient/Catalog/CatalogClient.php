<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use TopiPaymentIntegration\ApiClient\Common\ProductReferenceCollection;
use TopiPaymentIntegration\ApiClient\Common\ProductSummary;
use TopiPaymentIntegration\ApiClient\PreProcessOptionsTrait;

class CatalogClient
{
    use PreProcessOptionsTrait;

    /** @var array<string, array<string, mixed>> */
    private array $responseCache = [];

    public function __construct(
        private readonly GuzzleClient $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @param array<mixed> $options */
    public function importCatalog(ProductBatch $productBatch, array $options = []): void
    {
        $jsonData = [
            'products' => $productBatch->getProducts(),
        ];

        $start = microtime(true);
        $response = $this->client->post('catalog/import', array_merge([
            'json' => $jsonData,
        ], $options));

        $time_elapsed_secs = microtime(true) - $start;
        $this->logger->debug('Topi API took: '.$time_elapsed_secs.'s');

        $start = microtime(true);
        json_encode($jsonData);
        $time_elapsed_secs = microtime(true) - $start;

        $this->logger->debug('JSON encoding took: '.$time_elapsed_secs.'s');

        $this->logger->debug('Response: '.$response->getStatusCode().' Data: '.$response->getBody()->getContents());
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

        $start = microtime(true);
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

        $response = $this->client->post('catalog/check-supported', $this->preProcessOptions($requestOptions));

        $time_elapsed_secs = microtime(true) - $start;
        $this->logger->debug('Topi API took: '.$time_elapsed_secs.'s');
        $this->logger->debug('Response: '.$response->getStatusCode().' Data: '.$response->getBody());

        $responseData = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

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

        $start = microtime(true);
        try {
            $response = $this->client->post('catalog/pricing', $this->preProcessOptions(array_merge([
                'json' => $jsonData,
            ], $options)));
        } catch (RequestException $e) {
            if (!$e->hasResponse()) {
                throw $e;
            }

            if (404 !== $e->getResponse()->getStatusCode()) {
                $this->logger->debug('Error: '.$e->getMessage().'; Response: '.$e->getResponse()->getBody());
            }

            throw $e;
        }

        $time_elapsed_secs = microtime(true) - $start;
        $this->logger->debug('Topi API took: '.$time_elapsed_secs.'s');

        $start = microtime(true);
        json_encode($jsonData);
        $time_elapsed_secs = microtime(true) - $start;

        $this->logger->debug('JSON encoding took: '.$time_elapsed_secs.'s');

        $this->logger->debug('Response: '.$response->getStatusCode().' Data: '.$response->getBody());

        $responseData = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $result = new CalculatePricingResponse();
        $result->applyData($responseData);

        return $result;
    }
}
