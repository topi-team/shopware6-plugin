<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient;

use Psr\Log\LoggerInterface;
use TopiPaymentIntegration\ApiClient\Catalog\CatalogClient;
use TopiPaymentIntegration\ApiClient\Factory\HttpClientFactory;
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
        readonly private HttpClientFactory $clientFactory,
        readonly private LoggerInterface $logger,
    ) {
    }

    public function catalog(Environment $environment): CatalogClient
    {
        $cacheKey = $environment->hash();
        if (!isset($this->catalogClients[$cacheKey])) {
            $this->catalogClients[$cacheKey] = new CatalogClient(
                $this->clientFactory->make($environment),
                $this->logger,
            );
        }

        return $this->catalogClients[$cacheKey];
    }

    public function shippingMethod(Environment $environment): ShippingMethodClient
    {
        $cacheKey = $environment->hash();
        if (!isset($this->shippingMethodClients[$cacheKey])) {
            $this->shippingMethodClients[$cacheKey] = new ShippingMethodClient(
                $this->clientFactory->make($environment),
            );
        }

        return $this->shippingMethodClients[$cacheKey];
    }

    public function offer(Environment $environment): OfferClient
    {
        $cacheKey = $environment->hash();
        if (!isset($this->offerClients[$cacheKey])) {
            $this->offerClients[$cacheKey] = new OfferClient(
                $this->clientFactory->make($environment),
                $this->logger,
            );
        }

        return $this->offerClients[$cacheKey];
    }

    public function order(Environment $environment): OrderClient
    {
        $cacheKey = $environment->hash();
        if (!isset($this->orderClients[$cacheKey])) {
            $this->orderClients[$cacheKey] = new OrderClient(
                $this->clientFactory->make($environment),
                $this->logger,
            );
        }

        return $this->orderClients[$cacheKey];
    }
}
