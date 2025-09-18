<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\CatalogSyncBatch;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TopiPaymentIntegration\ApiClient\Catalog\ProductBatch;
use TopiPaymentIntegration\ApiClient\Client;
use TopiPaymentIntegration\ApiClient\Factory\EnvironmentFactory;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchCollection;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchEntity;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchStatusEnum;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessEntity;
use TopiPaymentIntegration\Content\Product\SalesChannel\RawSalesChannelProductDefinition;
use TopiPaymentIntegration\Service\ShopwareProductToTopiProductConverter;
use TopiPaymentIntegration\Service\SwpOptionToTopiProductConverter;
use TopiPaymentIntegration\Util\ContextHelper;

#[AsMessageHandler(handles: CatalogSyncBatchMessage::class)]
readonly class CatalogSyncBatchHandler
{
    /**
     * @param EntityRepository<CatalogSyncBatchCollection>          $catalogSyncBatchRepository
     * @param SalesChannelRepository<SalesChannelProductCollection> $salesChannelRepository
     */
    public function __construct(
        private EntityRepository $catalogSyncBatchRepository,
        private SalesChannelRepository $salesChannelRepository,
        private AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private ShopwareProductToTopiProductConverter $productConverter,
        private Client $apiClient,
        private EnvironmentFactory $environmentFactory,
        private ?EntityRepository $swpOptionsRepository = null,
        private ?SwpOptionToTopiProductConverter $optionConverter = null,
    ) {
    }

    public function __invoke(CatalogSyncBatchMessage $message, ?Context $context = null): void
    {
        $context = $context ?? ContextHelper::createCliContext();
        $criteria = (new Criteria([$message->catalogSyncBatchId]))
                        ->addAssociation('catalogSyncProcess')
                        ->addAssociation('catalogSyncProcess.salesChannel')
                        ->addAssociation('catalogSyncProcess.salesChannel.domains')
                        ->addAssociation('catalogSyncProcess.salesChannel.currency');

        /** @var CatalogSyncBatchEntity $batch */
        $batch = $this->catalogSyncBatchRepository->search($criteria, $context)->first();

        try {
            $this->run($batch);

            $this->catalogSyncBatchRepository->update([[
                'id' => $batch->getId(),
                'status' => CatalogSyncBatchStatusEnum::COMPLETED->value,
            ]], $context);
        } catch (\Exception $e) {
            $this->catalogSyncBatchRepository->update([[
                'id' => $batch->getId(),
                'status' => CatalogSyncBatchStatusEnum::ERROR->value,
            ]], $context);

            throw $e;
        }
    }

    private function run(CatalogSyncBatchEntity $batch): void
    {
        $process = $batch->getCatalogSyncProcess();
        assert($process instanceof CatalogSyncProcessEntity);

        $salesChannel = $process->getSalesChannel();
        assert($salesChannel instanceof SalesChannelEntity);

        $salesChannelContext = $this->salesChannelContextFactory->create(
            '',
            $salesChannel->getId(),
            [SalesChannelContextService::LANGUAGE_ID => $salesChannel->getLanguageId()]
        );

        $identifiersByType = $this->groupIdentifiersByType($batch->getItemIdentifiers());

        $topiProductBatch = new ProductBatch();

        if (isset($identifiersByType[CatalogSyncBatchEntity::ITEM_TYPE_PRODUCT])) {
            /** @var SalesChannelProductEntity $product */
            foreach ($this->queryProductEntities(
                $identifiersByType[CatalogSyncBatchEntity::ITEM_TYPE_PRODUCT],
                $salesChannelContext
            ) as $product) {
                $topiProductBatch->add($this->productConverter->convert($product, $salesChannel));
            }
        }

        // Optionally append SWP options as standalone products
        if ($this->swpOptionsRepository && $this->optionConverter && isset($identifiersByType[CatalogSyncBatchEntity::ITEM_TYPE_SWP_PRODUCT_OPTION])) {
            $options = $this->queryProductOptions($identifiersByType[CatalogSyncBatchEntity::ITEM_TYPE_SWP_PRODUCT_OPTION]);

            foreach ($options as $opt) {
                $topiProductBatch->add($this->optionConverter->convert($opt, $salesChannel));
            }
        }

        $this->apiClient->catalog(
            $this->environmentFactory->makeEnvironment($salesChannel->getId()),
        )->importCatalog($topiProductBatch);
    }

    private function queryProductOptions(array $optionIds): EntityCollection
    {
        // Fetch mappings: options assigned to products in this batch
        $criteria = (new Criteria($optionIds))
            ->addAssociation('translations')
            ->addAssociation('media')
            ->addAssociation('tax');

        return $this->swpOptionsRepository->search($criteria, ContextHelper::createCliContext())->getEntities();
    }

    private function groupIdentifiersByType(array $identifiers): array
    {
        $grouped = [];
        foreach ($identifiers as $identifier) {
            $grouped[$identifier['type']][] = $identifier['id'];
        }

        return $grouped;
    }

    /**
     * Queries product entities based on provided product IDs and associated sales channel context.
     *
     * @param string[]            $productIds          list of product IDs to query
     * @param SalesChannelContext $salesChannelContext the sales channel context for executing the query
     *
     * @return EntityCollection<ProductEntity>
     */
    private function queryProductEntities(
        array $productIds,
        SalesChannelContext $salesChannelContext,
    ): EntityCollection {
        $criteria = (new Criteria($productIds))
            ->addAssociation('translations')
            ->addAssociation('manufacturer')
            ->addAssociation('categories')
            ->addAssociation('categoriesRo')
            ->addAssociation('properties')
            ->addAssociation('properties.group')
            ->addAssociation('options.group')
            ->addAssociation('options')
            ->addAssociation('seoUrls')
            ->addAssociation('cover')
            ->addAssociation('cover.media');

        $criteria->addState(RawSalesChannelProductDefinition::SKIP_DEFAULT_AVAILABLE_FILTER);

        return $this->salesChannelRepository->search($criteria, $salesChannelContext)->getEntities();
    }
}
