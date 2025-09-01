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
        $references = $this->extractProductReferences($lineItems);
        $uniqueKeys = array_keys($references); // keys are cache keys

        /** @var iterable<string, CacheItemInterface> $cacheResults */
        $cacheResults = $this->cache->getItems($uniqueKeys);

        $knownAvailabilities = [];
        $availabilitiesToRequest = new ProductReferenceCollection();

        foreach ($cacheResults as $key => $cacheResult) {
            if ($cacheResult->isHit()) {
                /** @var ProductSummary $summary */
                $summary = $cacheResult->get();
                $knownAvailabilities[$key] = $summary;
                continue;
            }

            $pair = $references[$key];
            $ref = new ProductReference();
            $ref->source = $pair['source'];
            $ref->reference = $pair['reference'];
            $availabilitiesToRequest->add($ref);
        }

        if (count($availabilitiesToRequest) > 0) {
            $availabilities = $this->client->catalog(
                $this->environmentFactory->makeEnvironment($salesChannelId)
            )->checkSupported($availabilitiesToRequest);

            foreach ($availabilities as $availability) {
                $cacheKey = $this->getCacheKeyFromPair([
                    'source' => $availability->sellerProductReference->source,
                    'reference' => $availability->sellerProductReference->reference,
                ]);
                $knownAvailabilities[$cacheKey] = $availability;

                $cacheItem = $this->cache->getItem($cacheKey);
                $cacheItem->expiresAfter(new \DateInterval('PT1H'));
                $cacheItem->set($availability);
                $this->cache->save($cacheItem);
            }
        }

        // All considered references must be supported
        foreach (array_keys($references) as $key) {
            if (!isset($knownAvailabilities[$key]) || !$knownAvailabilities[$key]->isSupported) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array{source:string, reference:string}> keyed by cache key
     */
    private function extractProductReferences(LineItemCollection $lineItems): array
    {
        $pairs = [];

        foreach ($lineItems as $item) {
            $type = $item->getType();

            if ($type === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $ref = (string) $item->getReferencedId();
                if ($ref !== '') {
                    $pair = ['source' => 'shopware-ids', 'reference' => $ref];
                    $pairs[$this->getCacheKeyFromPair($pair)] = $pair;
                }
                continue;
            }

            if ($type === 'product-with-options') {
                // add main product
                $ref = (string) $item->getReferencedId();
                if ($ref !== '') {
                    $pair = ['source' => 'shopware-ids', 'reference' => $ref];
                    $pairs[$this->getCacheKeyFromPair($pair)] = $pair;
                }

                // add each option child by its option id if available
                foreach ($item->getChildren() ?? [] as $child) {
                    if (!\in_array($child->getType(), ['product-option', 'product-option-product'], true)) {
                        continue;
                    }
                    $optRef = (string) ($child->getPayload()['optionValue'] ?? $child->getReferencedId() ?? '');
                    if ($optRef !== '') {
                        $pair = ['source' => 'swp-option-id', 'reference' => $optRef];
                        $pairs[$this->getCacheKeyFromPair($pair)] = $pair;
                    }
                }
            }
        }

        return $pairs;
    }

    /**
     * @param array{source:string, reference:string}|ProductSummary $pairOrSummary
     */
    private function getCacheKeyFromPair(array|ProductSummary $pairOrSummary): string
    {
        if ($pairOrSummary instanceof ProductSummary) {
            $source = $pairOrSummary->sellerProductReference->source;
            $reference = $pairOrSummary->sellerProductReference->reference;
        } else {
            $source = $pairOrSummary['source'];
            $reference = $pairOrSummary['reference'];
        }

        return self::CACHE_PREFIX . $source . ':' . $reference;
    }
}
