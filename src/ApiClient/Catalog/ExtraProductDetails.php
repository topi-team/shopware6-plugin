<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class ExtraProductDetails implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public string $property;

    public string $value;
}
