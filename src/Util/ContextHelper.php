<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Util;

use Shopware\Core\Framework\Context;

final class ContextHelper
{
    public static function createCliContext(): Context
    {
        if (method_exists(Context::class, 'createCLIContext')) {
            /* @phpstan-ignore-next-line */
            return Context::createCLIContext();
        }

        return Context::createDefaultContext();
    }
}
