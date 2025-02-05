<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Common;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;

class ContractTermsSummary implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait;

    public ?bool $canPayNow = null;

    public ?bool $canRent = null;

    public ProductRentContract $rent;
}
