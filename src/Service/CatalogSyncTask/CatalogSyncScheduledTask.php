<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\CatalogSyncTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CatalogSyncScheduledTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'topi_payment_integration.catalog_sync_task';
    }

    public static function getDefaultInterval(): int
    {
        return 300;
    }
}
