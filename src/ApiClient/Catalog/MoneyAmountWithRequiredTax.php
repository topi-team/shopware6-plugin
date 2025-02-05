<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\Common\MoneyAmount;

class MoneyAmountWithRequiredTax extends MoneyAmount
{
    public int $taxRate;
}
