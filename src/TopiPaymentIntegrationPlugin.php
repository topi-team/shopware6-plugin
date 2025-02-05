<?php

declare(strict_types=1);

namespace TopiPaymentIntegration;

use Shopware\Core\Framework\Plugin;

class TopiPaymentIntegrationPlugin extends Plugin
{
    public static function getPluginDir(): string
    {
        return __DIR__;
    }
}
