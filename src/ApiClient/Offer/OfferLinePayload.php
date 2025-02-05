<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use TopiPaymentIntegration\ApiClient\Common\MoneyAmount;
use TopiPaymentIntegration\ApiClient\Common\ProductReference;
use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class OfferLinePayload implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public MoneyAmount $price;

    public int $quantity;

    public ProductReference $sellerProductReference;

    public string $title;

    public ?string $subtitle = null;
}
