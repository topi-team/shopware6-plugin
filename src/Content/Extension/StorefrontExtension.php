<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\Extension;

use Shopware\Core\Framework\Struct\Struct;

class StorefrontExtension extends Struct
{
    public const EXTENSION_NAME = 'topiPaymentIntegrationStorefrontExtension';

    public function __construct(
        public string $widgetJsUrl,
        public string $widgetId,
        public string $paymentMethodId,
    ) {
    }
}
