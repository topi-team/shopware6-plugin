<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Order;

use TopiPaymentIntegration\ApiClient\Common\ProductReference;

class Asset
{
    public string $id;

    public string $productId;

    /**
     * @var ProductReference[]
     */
    public array $sellerProductReferences;

    public ?string $serialNumber = null;

    public string $title;

    public ?string $trackingUrl = null;
}
