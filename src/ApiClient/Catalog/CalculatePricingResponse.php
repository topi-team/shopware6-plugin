<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;

class CalculatePricingResponse implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait {
        applyData as commonApplyData;
    }

    /**
     * @var string[]
     */
    public array $availableContractTypes = [];

    public bool $isSupported;

    public ?PayNowContractTerm $payNow = null;

    /** @var RentContractTerm[] */
    public array $rent = [];

    public string $summary;

    public function applyData(array $data): void
    {
        if (isset($data['rent'])) {
            foreach ($data['rent'] as $rentContractTerm) {
                $rentContractTermObject = new RentContractTerm();
                $rentContractTermObject->applyData($rentContractTerm);

                $this->rent[] = $rentContractTermObject;
            }

            unset($data['rent']);
        }

        $this->commonApplyData($data);
    }
}
