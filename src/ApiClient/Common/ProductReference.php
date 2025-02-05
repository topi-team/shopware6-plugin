<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Common;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;
use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class ProductReference implements \JsonSerializable, AppliesArrayDataInterface
{
    use JsonSerializeLowerSnakeCaseTrait;
    use AppliesArrayDataTrait;

    public string $source;

    public string $reference;
}
