<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\EventProcessing;

use Shopware\Core\Framework\Context;
use TopiPaymentIntegration\Event\EventInterface;

interface ProcessorInterface
{
    public function canProcess(string $event): bool;

    public function process(EventInterface $event, Context $context): void;
}
