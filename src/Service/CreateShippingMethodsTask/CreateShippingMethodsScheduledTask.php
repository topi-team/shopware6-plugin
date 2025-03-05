<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\CreateShippingMethodsTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CreateShippingMethodsScheduledTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'topi_payment_integration.create_shipping_methods';
    }

    public static function getDefaultInterval(): int
    {
        return 86_400; // 1 day
    }
}
