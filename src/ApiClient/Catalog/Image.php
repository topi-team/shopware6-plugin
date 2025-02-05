<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class Image implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public string $url;
}
