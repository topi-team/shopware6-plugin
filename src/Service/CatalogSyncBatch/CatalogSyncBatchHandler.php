<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\CatalogSyncBatch;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TopiPaymentIntegration\ApiClient\Catalog\ProductBatch;
use TopiPaymentIntegration\ApiClient\Client;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchEntity;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessEntity;
use TopiPaymentIntegration\Service\ShopwareProductToTopiProductConverter;

#[AsMessageHandler(handles: CatalogSyncBatchMessage::class)]
readonly class CatalogSyncBatchHandler
{
    public function __construct(
        private EntityRepository $catalogSyncBatchRepository,
        private SalesChannelRepository $salesChannelRepository,
        private AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private ShopwareProductToTopiProductConverter $productConverter,
        private Client $apiClient,
    ) {
    }

    public function __invoke(CatalogSyncBatchMessage $message): void
    {
        $context = Context::createDefaultContext();
        $criteria = (new Criteria([$message->catalogSyncBatchId]))
                        ->addAssociation('catalogSyncProcess')
                        ->addAssociation('catalogSyncProcess.salesChannel')
                        ->addAssociation('catalogSyncProcess.salesChannel.domains')
                        ->addAssociation('catalogSyncProcess.salesChannel.currency');

        /** @var CatalogSyncBatchEntity $batch */
        $batch = $this->catalogSyncBatchRepository->search($criteria, $context)->first();

        $process = $batch->getCatalogSyncProcess();
        assert($process instanceof CatalogSyncProcessEntity);

        $salesChannel = $process->getSalesChannel();
        assert($salesChannel instanceof SalesChannelEntity);

        $criteria = (new Criteria($batch->getProductIds()))
            ->addAssociation('translations')
            ->addAssociation('categories')
            ->addAssociation('categoriesRo')
            ->addAssociation('properties')
            ->addAssociation('properties.group')
            ->addAssociation('seoUrls');
        $salesChannelContext = $this->salesChannelContextFactory->create(
            '',
            $salesChannel->getId(),
            [SalesChannelContextService::LANGUAGE_ID => $salesChannel->getLanguageId()]
        );

        $products = $this->salesChannelRepository->search($criteria, $salesChannelContext)->getEntities();

        $batch = new ProductBatch();
        /** @var SalesChannelProductEntity $product */
        foreach ($products as $product) {
            $batch->add($this->productConverter->convert($product, $salesChannel));
        }

        $this->apiClient->catalog()->importCatalog($batch);
    }
}
