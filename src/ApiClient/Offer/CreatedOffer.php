<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;

class CreatedOffer extends BaseOffer implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait;

    public string $checkoutRedirectUrl = '';

    public \DateTime $createdAt;

    public string $id;

    /**
     * @var 'created'|'voided'|'accepted'|'expired'|'rejected'|'pending_review'|'declined'
     */
    public string $status;
}
