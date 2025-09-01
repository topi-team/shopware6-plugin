<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\CatalogSyncBatch;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
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
        private ?EntityRepository $swpProductToOptionsRepository = null,
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

        /**
         * add an empty ProductAvailableFilter so the SalesChannelRepository does not remove inactive / invisible products.
         *
         * @see \Shopware\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface::processCriteria
         * @see \Shopware\Core\Content\Product\SalesChannel\SalesChannelProductDefinition::processCriteria
         */
        $criteria = (new Criteria($batch->getProductIds()))
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

        $products = $this->salesChannelRepository->search($criteria, $salesChannelContext)->getEntities();

        $topiProductBatch = new ProductBatch();
        /** @var SalesChannelProductEntity $product */
        foreach ($products as $product) {
            $topiProductBatch->add($this->productConverter->convert($product, $salesChannel));
        }

        // Optionally append SWP options as standalone products
        if ($this->swpProductToOptionsRepository && $this->optionConverter) {
            $this->appendSwpOptionsToBatch($topiProductBatch, $batch->getProductIds(), $salesChannel);
        }

        $this->apiClient->catalog(
            $this->environmentFactory->makeEnvironment($salesChannel->getId()),
        )->importCatalog($topiProductBatch);
    }

    private function appendSwpOptionsToBatch(ProductBatch $batch, array $productIds, SalesChannelEntity $salesChannel): void
    {
        // Fetch mappings: options assigned to products in this batch
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));
        // Follow mapping: product_to_options.option (group-option mapping) -> productoptionsoption (actual option)
        $criteria->addAssociation('option.productoptionsoption');
        $criteria->addAssociation('option.productoptionsoption.translations');
        $criteria->addAssociation('option.productoptionsoption.media');
        $criteria->addAssociation('option.productoptionsoption.tax');

        $result = $this->swpProductToOptionsRepository->search($criteria, ContextHelper::createCliContext());

        $options = [];
        foreach ($result->getEntities() as $mapping) {
            // mapping->getOption() returns the group-option mapping; then ->getProductoptionsoption() yields the Option entity
            $groupAssigned = method_exists($mapping, 'getOption') ? $mapping->getOption() : null;
            $option = $groupAssigned && method_exists($groupAssigned, 'getProductoptionsoption') ? $groupAssigned->getProductoptionsoption() : null;
            if (!$option || !method_exists($option, 'getId')) {
                continue;
            }
            $options[$option->getId()] = $option;
        }

        foreach ($options as $opt) {
            $batch->add($this->optionConverter->convert($opt, $salesChannel));
        }
    }
}
