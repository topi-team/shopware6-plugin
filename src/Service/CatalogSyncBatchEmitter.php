<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchDefinition;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchStatusEnum;

/**
 * @phpstan-import-type CatalogSyncBatchData from CatalogSyncBatchDefinition
 */
readonly class CatalogSyncBatchEmitter
{
    /**
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(
        private EntityRepository $productRepository,
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
                yield [
                    'productIds' => $batchIds,
                    'status' => CatalogSyncBatchStatusEnum::NEW->value,
                ];
            }

            $nextOffset += $batchSize;
        } while (count($batchIds) > 0);
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
