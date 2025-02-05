<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class Category implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public string $id;

    public string $name;

    public string $parentCategoryId;
}
