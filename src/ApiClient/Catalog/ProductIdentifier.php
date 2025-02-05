<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class ProductIdentifier implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public string $id;

    public string $identifierType;
}
