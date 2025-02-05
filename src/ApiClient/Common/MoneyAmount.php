<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Common;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;
use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class MoneyAmount implements \JsonSerializable, AppliesArrayDataInterface
{
    use JsonSerializeLowerSnakeCaseTrait;
    use AppliesArrayDataTrait;

    public string $currency;

    public int $gross;

    public int $net;

    public function getNetFormatted(): float
    {
        return $this->net / 100;
    }

    public function getGrossFormatted(): float
    {
        return $this->gross / 100;
    }
}
