<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use TopiPaymentIntegration\ApiClient\Common\MoneyAmount;
use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class ShippingInfo implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public MoneyAmount $price;

    public string $sellerShippingReference;
}
