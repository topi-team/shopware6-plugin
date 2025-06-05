<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\Extension;

use Shopware\Core\Framework\Struct\Struct;
use TopiPaymentIntegration\ApiClient\Catalog\MoneyAmountWithOptionalTax;
use TopiPaymentIntegration\ApiClient\Common\ProductReference;

class ProductExtension extends Struct
{
    public const EXTENSION_NAME = 'topiWidgetProduct';

    public function __construct(
        public MoneyAmountWithOptionalTax $price,
        public ProductReference $sellerProductReference,
        public int $quantity = 1,
    ) {
    }
}
