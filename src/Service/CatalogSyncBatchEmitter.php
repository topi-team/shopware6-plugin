<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\BucketResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchDefinition;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchEntity;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchStatusEnum;
use TopiPaymentIntegration\Util\ContextHelper;
use TopiPaymentIntegration\Util\GeneratorHelper;

/**
 * @phpstan-import-type CatalogSyncBatchItemIdentifier from CatalogSyncBatchEntity
 * @phpstan-import-type CatalogSyncBatchData from CatalogSyncBatchDefinition
 */
class CatalogSyncBatchEmitter
{
    private array $seenItemIdentifiers = [
        CatalogSyncBatchEntity::ITEM_TYPE_PRODUCT => [],
        CatalogSyncBatchEntity::ITEM_TYPE_SWP_PRODUCT_OPTION => [],
    ];

    /**
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly ?EntityRepository $swpProductToOptionsRepository = null,
    ) {
    }

    /**
     * @return \Generator<CatalogSyncBatchData>
     */
    public function emit(int $batchSize, Context $context, ?Criteria $criteria = null): \Generator
    {
        $nextOffset = 0;
        do {
            $criteria = ($criteria ?? new Criteria())
                ->setLimit($batchSize)
                ->setOffset($nextOffset);
            $batchIds = $this->productRepository->searchIds($criteria, $context)->getIds();

            if (!empty($batchIds)) {
                $identifiers = $this->buildBatchItemsFromProductIds($batchIds);

                foreach (GeneratorHelper::chunkGenerator($identifiers, $batchSize) as $chunk) {
                    yield [
                        'itemIdentifiers' => $chunk,
                        'status' => CatalogSyncBatchStatusEnum::NEW->value,
                    ];
                }
            }

            $nextOffset += $batchSize;
        } while (count($batchIds) > 0);
    }

    /**
     * @param string[] $productIds
     * @return \Generator<CatalogSyncBatchItemIdentifier>
     */
    private function buildBatchItemsFromProductIds(array $productIds): \Generator
    {
        foreach ($productIds as $productId) {
            if (in_array($productId, $this->seenItemIdentifiers[CatalogSyncBatchEntity::ITEM_TYPE_PRODUCT], true)) {
                continue;
            }

            $this->seenItemIdentifiers[CatalogSyncBatchEntity::ITEM_TYPE_PRODUCT][] = $productId;
            yield [
                'type' => CatalogSyncBatchEntity::ITEM_TYPE_PRODUCT,
                'id' => $productId,
            ];
        }

        if ($this->swpProductToOptionsRepository) {
            foreach ($this->appendSwpOptionsToBatch($productIds) as $item) {
                if (in_array($item['id'], $this->seenItemIdentifiers[$item['type']], true)) {
                    continue;
                }

                $this->seenItemIdentifiers[$item['type']][] = $item['id'];
                yield $item;
            }
        }
    }

    private function appendSwpOptionsToBatch(array $productIds): \Generator
    {
        // Fetch mappings: options assigned to products in this batch
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));
        $criteria->addAggregation(new TermsAggregation(
            'option-ids',
            'option.swpProductOptionsOptionsId',
        ));

        $result = $this->swpProductToOptionsRepository->aggregate($criteria, ContextHelper::createCliContext());
        $terms = $result->get('option-ids');
        if (!$terms instanceof BucketResult) {
            return;
        }

        foreach ($terms->getBuckets() as $bucket) {
            $optionId = $bucket->getKey();
            if (!$optionId) {
                continue;
            }

            yield [
                'type' => CatalogSyncBatchEntity::ITEM_TYPE_SWP_PRODUCT_OPTION,
                'id' => $optionId,
            ];
        }
    }

    public function countProducts(Context $context, ?Criteria $criteria = null): int
    {
        $criteria = ($criteria ?? new Criteria())
            ->addAggregation(new CountAggregation('product-count', 'id'));

        /** @var CountResult $aggregate */
        $aggregate = $this->productRepository->aggregate($criteria, $context)->get('product-count');

        return $aggregate->getCount();
    }
}
