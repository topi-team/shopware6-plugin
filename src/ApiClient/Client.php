<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient;

use Psr\Log\LoggerInterface;
use TopiPaymentIntegration\ApiClient\Catalog\CatalogClient;
use TopiPaymentIntegration\ApiClient\Factory\GuzzleClientFactory;
use TopiPaymentIntegration\ApiClient\Offer\OfferClient;
use TopiPaymentIntegration\ApiClient\Order\OrderClient;
use TopiPaymentIntegration\ApiClient\ShippingMethod\ShippingMethodClient;

class Client
{
    /**
     * @var CatalogClient[]
     */
    private array $catalogClients = [];

    /**
     * @var ShippingMethodClient[]
     */
    private array $shippingMethodClients = [];

    /**
     * @var OfferClient[]
     */
    private array $offerClients = [];

    /**
     * @var OrderClient[]
     */
    private array $orderClients = [];

    public function __construct(
        private GuzzleClientFactory $clientFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function catalog(?string $clientId = null, ?string $clientSecret = null): CatalogClient
    {
        $cacheKey = $this->getCacheKey($clientId, $clientSecret);
        if (!isset($this->catalogClients[$cacheKey])) {
            $this->catalogClients[$cacheKey] = new CatalogClient(
                $this->clientFactory->make($clientId, $clientSecret),
                $this->logger,
            );
        }

        return $this->catalogClients[$cacheKey];
    }

    public function shippingMethod(?string $clientId = null, ?string $clientSecret = null): ShippingMethodClient
    {
        $cacheKey = $this->getCacheKey($clientId, $clientSecret);
        if (!isset($this->shippingMethodClients[$cacheKey])) {
            $this->shippingMethodClients[$cacheKey] = new ShippingMethodClient(
                $this->clientFactory->make($clientId, $clientSecret),
            );
        }

        return $this->shippingMethodClients[$cacheKey];
    }

    public function offer(?string $clientId = null, ?string $clientSecret = null): OfferClient
    {
        $cacheKey = $this->getCacheKey($clientId, $clientSecret);
        if (!isset($this->offerClients[$cacheKey])) {
            $this->offerClients[$cacheKey] = new OfferClient(
                $this->clientFactory->make($clientId, $clientSecret),
                $this->logger,
            );
        }

        return $this->offerClients[$cacheKey];
    }

    public function order(?string $clientId = null, ?string $clientSecret = null): OrderClient
    {
        $cacheKey = $this->getCacheKey($clientId, $clientSecret);
        if (!isset($this->orderClients[$cacheKey])) {
            $this->orderClients[$cacheKey] = new OrderClient(
                $this->clientFactory->make($clientId, $clientSecret),
                $this->logger,
            );
        }

        return $this->orderClients[$cacheKey];
    }

    private function getCacheKey(?string $clientId = null, ?string $clientSecret = null): string
    {
        return ($clientId ?? 'default').':'.($clientSecret ?? 'default');
    }
}
