<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\EventProcessing;

use Shopware\Core\Framework\Context;
use TopiPaymentIntegration\Event\EventInterface;

class CommonProcessor implements ProcessorInterface
{
    /**
     * @var ProcessorInterface[]
     */
    private array $processorList = [];

    public function __construct(iterable $processors)
    {
        foreach ($processors as $processor) {
            $this->processorList[] = $processor;
        }
    }

    public function canProcess(string $event): bool
    {
        foreach ($this->processorList as $processor) {
            if ($processor->canProcess($event)) {
                return true;
            }
        }

        return false;
    }

    public function process(EventInterface $event, Context $context): void
    {
        foreach ($this->processorList as $processor) {
            if ($processor->canProcess($event->getEvent())) {
                $processor->process($event, $context);
            }
        }
    }
}
