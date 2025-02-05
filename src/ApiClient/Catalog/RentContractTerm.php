<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;

class RentContractTerm implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait;

    public int $duration;

    public ?string $id = null;

    public MoneyAmountWithRequiredTax $monthlyAmount;
}
