<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Common;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;

class ProductSummary implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait;

    public ContractTermsSummary $availableContractTerms;

    public ?string $id = null;

    public ?bool $isSupported = null;

    public ProductReference $sellerProductReference;

    /** @return array<"rent"|"pay_now"> */
    public function convertToAvailableContractTermsList(): array
    {
        $availableContractTerms = [];
        if ($this->availableContractTerms->canRent) {
            $availableContractTerms[] = 'rent';
        }
        if ($this->availableContractTerms->canPayNow) {
            $availableContractTerms[] = 'pay_now';
        }

        return $availableContractTerms;
    }
}
