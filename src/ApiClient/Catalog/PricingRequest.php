<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\Common\ProductReference;
use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class PricingRequest implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public MoneyAmountWithOptionalTax $price;

    public ProductReference $sellerProductReference;

    public function getHash(): string
    {
        return md5(serialize([
            $this->price->jsonSerialize(),
            $this->sellerProductReference->jsonSerialize(),
        ]));
    }
}
