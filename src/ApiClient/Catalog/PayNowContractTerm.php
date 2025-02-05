<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;

class PayNowContractTerm implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait;

    public ?string $id = null;

    public MoneyAmountWithRequiredTax $amount;
}
