<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\ShippingMethod;

use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class ShippingMethod implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public string $name;

    public string $sellerShippingMethodReference;
}
