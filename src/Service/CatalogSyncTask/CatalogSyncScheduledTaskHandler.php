<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\CatalogSyncTask;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use TopiPaymentIntegration\Util\ContextHelper;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TopiPaymentIntegration\Action\SyncCatalogAction;
use TopiPaymentIntegration\CatalogSyncContext;

#[AsMessageHandler(handles: CatalogSyncScheduledTask::class)]
class CatalogSyncScheduledTaskHandler extends ScheduledTaskHandler
{
    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $exceptionLogger,
        private readonly SyncCatalogAction $syncCatalogAction,
    ) {
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    public function run(): void
    {
        $context = ContextHelper::createCliContext();

        $this->syncCatalogAction->execute($context, new CatalogSyncContext());
    }
}
