<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Util;

use Shopware\Core\Framework\Context;

final class ContextHelper
{
    public static function createCliContext(): Context
    {
        // Context::createCLIContext() does not exist in Shopware 6.5 and early 6.6, which this plugin still supports.
        if (method_exists(Context::class, 'createCLIContext')) { // @phpstan-ignore function.alreadyNarrowedType
            return Context::createCLIContext();
        }

        return Context::createDefaultContext(); // @phpstan-ignore shopware.disallow.default.context.creation
    }
}
