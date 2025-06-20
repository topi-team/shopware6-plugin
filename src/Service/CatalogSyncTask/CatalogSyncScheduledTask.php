<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\CatalogSyncTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CatalogSyncScheduledTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'topi_payment_integration.catalog_sync';
    }

    public static function getDefaultInterval(): int
    {
        return 21_600; // every 6 hours
    }
}
