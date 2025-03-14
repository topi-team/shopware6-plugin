<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use TopiPaymentIntegration\ApiClient\Client;
use TopiPaymentIntegration\ApiClient\Common\ProductReference;
use TopiPaymentIntegration\ApiClient\Common\ProductReferenceCollection;
use TopiPaymentIntegration\ApiClient\Common\ProductSummary;
use TopiPaymentIntegration\ApiClient\Factory\EnvironmentFactory;

readonly class CartAvailabilityService
{
    private const CACHE_PREFIX = 'topi_cart_availability_';

    public function __construct(
        private Client $client,
        private EnvironmentFactory $environmentFactory,
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function isCartAvailableForPurchaseThroughTopi(LineItemCollection $lineItems, string $salesChannelId): bool
    {
        $cacheKeys = $lineItems->map($this->getCacheKey(...));
        /** @var iterable<string, CacheItemInterface> $cacheResults */
        $cacheResults = $this->cache->getItems($cacheKeys);

        $knownAvailabilities = [];

        $availabilitiesToRequest = new ProductReferenceCollection();
        foreach ($cacheResults as $key => $cacheResult) {
            $referencedId = substr($key, strlen(self::CACHE_PREFIX));
            if ($cacheResult->isHit()) {
                $knownAvailabilities[$referencedId] = $cacheResult->get();
                continue;
            }

            $reference = new ProductReference();
            $reference->reference = $referencedId;
            $reference->source = 'shopware-ids';

            $availabilitiesToRequest->add($reference);
        }

        $availabilities = $this->client->catalog(
            $this->environmentFactory->makeEnvironment($salesChannelId)
        )->checkSupported($availabilitiesToRequest);

        foreach ($availabilities as $availability) {
            $knownAvailabilities[$availability->sellerProductReference->reference] = $availability;

            $cacheItem = $this->cache->getItem($this->getCacheKey($availability));
            $cacheItem->expiresAfter(new \DateInterval('PT1H'));
            $cacheItem->set($availability);

            $this->cache->save($cacheItem);
        }

        $supportedProducts = array_filter($knownAvailabilities, static fn (ProductSummary $productSummary) => $productSummary->isSupported);

        return count($supportedProducts) === count($lineItems);
    }

    private function getCacheKey(LineItem|ProductSummary $lineItemOrProductSummary): string
    {
        $id = $lineItemOrProductSummary instanceof LineItem
            ? $lineItemOrProductSummary->getReferencedId()
            : $lineItemOrProductSummary->sellerProductReference->reference;

        return self::CACHE_PREFIX.$id;
    }
}
