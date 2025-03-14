<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TopiPaymentIntegration\ApiClient\Common\ProductReferenceCollection;
use TopiPaymentIntegration\ApiClient\Common\ProductSummary;
use TopiPaymentIntegration\ApiClient\Exception\TopiApiException;
use TopiPaymentIntegration\ApiClient\PreProcessOptionsTrait;

readonly class CatalogClient
{
    use PreProcessOptionsTrait;

    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger,
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
     */
    public function checkSupported(ProductReferenceCollection $productReferences, array $options = []): array
    {
        if (0 === $productReferences->count()) {
            return [];
        }

        $jsonData = [
            'seller_product_references' => $productReferences->getProductReferences(),
        ];

        try {
            $response = $this->client->request('POST', 'catalog/check-supported',
                $this->preProcessOptions(array_merge([
                    'json' => $jsonData,
                ], $options))
            );

            $responseData = $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error($e);
            throw new TopiApiException($e->getMessage(), $e instanceof HttpExceptionInterface ? $e->getResponse()->getStatusCode() : $e->getCode(), $e);
        }

        $result = [];
        foreach ($responseData['products'] as $productSummaryData) {
            $item = new ProductSummary();
            $item->applyData($productSummaryData);

            $result[] = $item;
        }

        return $result;
    }
}
