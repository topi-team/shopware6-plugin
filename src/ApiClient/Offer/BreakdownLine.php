<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;
use TopiPaymentIntegration\ApiClient\Common\MoneyAmount;

class BreakdownLine implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait;

    public MoneyAmount $amount;

    public string $title;

    public ?string $tooltip = null;
}
