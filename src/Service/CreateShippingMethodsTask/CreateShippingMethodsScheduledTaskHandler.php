<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\CreateShippingMethodsTask;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TopiPaymentIntegration\Action\CreateShippingMethodsAction;

#[AsMessageHandler(handles: CreateShippingMethodsScheduledTask::class)]
class CreateShippingMethodsScheduledTaskHandler extends ScheduledTaskHandler
{
    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $exceptionLogger,
        private readonly CreateShippingMethodsAction $createShippingMethodsAction,
    ) {
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    public function run(): void
    {
        $context = Context::createCLIContext();

        $this->createShippingMethodsAction->execute($context);
    }
}
