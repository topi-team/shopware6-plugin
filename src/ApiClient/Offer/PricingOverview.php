<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;
use TopiPaymentIntegration\ApiClient\Common\MoneyAmount;

class PricingOverview implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait {
        applyData as commonApplyData;
    }

    /**
     * @var BreakdownLine[]
     */
    public array $breakdown = [];

    public MoneyAmount $insteadOfAmount;

    public MoneyAmount $shippingAmount;

    public MoneyAmount $totalAmount;

    public function applyData(array $data): void
    {
        if (isset($data['breakdown'])) {
            foreach ($data['breakdown'] as $breakdown) {
                $breakdownObject = new BreakdownLine();
                $breakdownObject->applyData($breakdown);

                $this->breakdown[] = $breakdownObject;
            }

            unset($data['breakdown']);
        }

        $this->commonApplyData($data);
    }
}
