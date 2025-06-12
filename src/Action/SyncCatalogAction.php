<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Action;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Symfony\Component\Messenger\MessageBusInterface;
use TopiPaymentIntegration\CatalogSyncContext;
use TopiPaymentIntegration\Config\ConfigValue;
use TopiPaymentIntegration\Config\PluginConfigService;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchCollection;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchDefinition;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchStatusEnum;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessCollection;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessDefinition;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessEntity;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessStatusEnum;
use TopiPaymentIntegration\Service\CatalogSyncBatch\CatalogSyncBatchHandler;
use TopiPaymentIntegration\Service\CatalogSyncBatch\CatalogSyncBatchMessage;
use TopiPaymentIntegration\Service\CatalogSyncBatchEmitter;
use TopiPaymentIntegration\TopiPaymentIntegrationPlugin;

readonly class SyncCatalogAction
{
    /**
     * @param EntityRepository<CatalogSyncProcessCollection> $catalogSyncProcessRepository
     * @param EntityRepository<CatalogSyncBatchCollection>   $catalogBatchRepository
     * @param EntityRepository<SalesChannelCollection>       $salesChannelRepository
     */
    public function __construct(
        private CatalogSyncBatchEmitter $batchEmitter,
        private EntityRepository $catalogSyncProcessRepository,
        private EntityRepository $catalogBatchRepository,
        private EntityRepository $salesChannelRepository,
        private PluginConfigService $pluginConfigService,
        private MessageBusInterface $messageBus,
        private CatalogSyncBatchHandler $catalogSyncBatchHandler,
    ) {
    }

    public function execute(Context $context, CatalogSyncContext $syncContext): void
    {
        if ($syncContext->useQueue && ($currentProcesses = $this->getCurrentSyncProcesses($context))->count() > 0) {
            foreach ($currentProcesses as $process) {
                $status = CatalogSyncBatchStatusEnum::getCounts();
                foreach ($process->getCatalogSyncBatches() as $batch) {
                    ++$status[$batch->getStatus()];
                }

                $batchCount = $process->getCatalogSyncBatches()->count();

                if ($status[CatalogSyncBatchStatusEnum::COMPLETED->value] === $batchCount) {
                    // all batches are completed
                    $this->completeProcess($process->getId(), $context);
                }

                if ($status[CatalogSyncBatchStatusEnum::ERROR->value] > 0) {
                    // more than one error occurred
                    $this->errorProcess($process->getId(), $context);
                }

                // when no previous if matched, the process is still running.
            }

            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('type.name', 'Storefront'));
        $salesChannels = $this->salesChannelRepository->searchIds($criteria, $context)->getIds();

        foreach ($salesChannels as $salesChannelId) {
            if ($this->pluginConfigService->getBool(ConfigValue::CATALOG_SYNC_ACTIVE_IN_SALES_CHANNEL, $salesChannelId)) {
                $this->processSalesChannel($salesChannelId, $context, $syncContext);
            }
        }
    }

    private function completeProcess(string $processId, Context $context): void
    {
        $this->catalogSyncProcessRepository->update([[
            'id' => $processId,
            'status' => CatalogSyncProcessStatusEnum::COMPLETED->value,
            'endDate' => new \DateTime(),
        ]], $context);
    }

    private function errorProcess(string $processId, Context $context): void
    {
        $this->catalogSyncProcessRepository->update([[
            'id' => $processId,
            'status' => CatalogSyncProcessStatusEnum::ERROR->value,
            'endDate' => new \DateTime(),
        ]], $context);
    }

    private function processSalesChannel(string $salesChannelId, Context $context, CatalogSyncContext $syncContext): void
    {
        $queries = array_map(
            static fn (string $categoryId) => new ContainsFilter('categoryTree', $categoryId),
            $this->pluginConfigService->get(ConfigValue::CATEGORIES, $salesChannelId) ?? [],
        );

        $criteria = new Criteria();
        if (!empty($queries)) {
            $criteria->addFilter(new OrFilter($queries));
        }

        $currentProcess = $this->createSyncProcess($salesChannelId, $context);
        $batches = $this->batchEmitter->emit(
            TopiPaymentIntegrationPlugin::CATALOG_SYNC_BATCH_SIZE,
            $context,
            $criteria,
        );

        // only count all products if we need the count for output
        if (!$syncContext->useQueue && $count = $this->batchEmitter->countProducts($context, $criteria)) {
            $syncContext->start($count);
        }

        /* @phpstan-ignore-next-line shopware.noEntityRepositoryInLoop as we batch inserts for the reason of saving memory */
        foreach ($batches as $batch) {
            [$entityId] = $this->catalogBatchRepository->create([[
                ...$batch,
                'catalogSyncProcessId' => $currentProcess->getId(),
            ]], $context)->getPrimaryKeys(CatalogSyncBatchDefinition::ENTITY_NAME);

            // just queue the tasks when using queue
            if ($syncContext->useQueue) {
                $this->messageBus->dispatch(new CatalogSyncBatchMessage($entityId));
                continue;
            }

            try {
                ($this->catalogSyncBatchHandler)(new CatalogSyncBatchMessage($entityId), $context);
            } catch (\Exception $e) {
                $syncContext->fail($e);

                $this->errorProcess($currentProcess->getId(), $context);

                throw $e;
            } finally {
                $syncContext->progress(count($batch['productIds']));
            }
        }

        if (!$syncContext->useQueue) {
            $this->completeProcess($currentProcess->getId(), $context);
        }

        $syncContext->success(sprintf('Finished sync for sales channel "%s"', $salesChannelId));
    }

    private function getCurrentSyncProcesses(Context $context): CatalogSyncProcessCollection
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('status', CatalogSyncProcessStatusEnum::IN_PROGRESS->value))
            ->addAssociation('catalogSyncBatches');

        return $this->catalogSyncProcessRepository->search($criteria, $context)->getEntities();
    }

    private function createSyncProcess(string $salesChannelId, Context $context): CatalogSyncProcessEntity
    {
        [$entityId] = $this->catalogSyncProcessRepository->create([[
            'status' => CatalogSyncProcessStatusEnum::IN_PROGRESS->value,
            'startDate' => new \DateTimeImmutable(),
            'salesChannelId' => $salesChannelId,
        ]], $context)->getPrimaryKeys(CatalogSyncProcessDefinition::ENTITY_NAME);

        /** @var CatalogSyncProcessEntity|null $entity */
        $entity = $this->catalogSyncProcessRepository->search(new Criteria([$entityId]), $context)->first();
        // we just wrote this entity, so it should be there
        assert($entity instanceof CatalogSyncProcessEntity);

        return $entity;
    }
}
