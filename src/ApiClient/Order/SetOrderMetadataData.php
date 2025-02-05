<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Order;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;

class SetOrderMetadataData implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait;

    public string $orderId;

    /**
     * @var array<string,string>|null
     */
    public ?array $metadata = null;
}
