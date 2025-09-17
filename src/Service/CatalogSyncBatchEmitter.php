<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
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
readonly class CatalogSyncBatchEmitter
{
    /**
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(
        private EntityRepository $productRepository,
        private ?EntityRepository $swpProductToOptionsRepository = null,
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
            yield [
                'type' => CatalogSyncBatchEntity::ITEM_TYPE_PRODUCT,
                'id' => $productId,
            ];
        }

        if ($this->swpProductToOptionsRepository) {
            yield from $this->appendSwpOptionsToBatch($productIds);
        }
    }

    private function appendSwpOptionsToBatch(array $productIds): \Generator
    {
        // Fetch mappings: options assigned to products in this batch
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));
        // Follow mapping: product_to_options.option (group-option mapping) -> productoptionsoption (actual option)
        $criteria->addAssociation('option.productoptionsoption');

        $result = $this->swpProductToOptionsRepository->search($criteria, ContextHelper::createCliContext());

        foreach ($result->getEntities() as $mapping) {
            // mapping->getOption() returns the group-option mapping; then ->getProductoptionsoption() yields the Option entity
            $groupAssigned = method_exists($mapping, 'getOption') ? $mapping->getOption() : null;
            $option = $groupAssigned && method_exists($groupAssigned, 'getProductoptionsoption') ? $groupAssigned->getProductoptionsoption() : null;
            if (!$option || !method_exists($option, 'getId')) {
                continue;
            }

            yield [
                'type' => CatalogSyncBatchEntity::ITEM_TYPE_SWP_PRODUCT_OPTION,
                'id' => $option->getId(),
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
