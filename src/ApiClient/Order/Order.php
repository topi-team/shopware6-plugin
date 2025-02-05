<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Order;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;
use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;

class Order implements AppliesArrayDataInterface
{
    use AppliesArrayDataTrait;

    public string $id;

    public string $offerId;

    public string $sellerOfferReference;

    /**
     * @var 'created'|'confirmed'|'acknowledged'|'accepted'|'partially_fulfilled'|'completed'|'canceled'|'rejected'
     */
    public string $status;

    /**
     * @var Asset[]
     */
    public array $assets;

    /**
     * @var array<string,string>|null
     */
    public ?array $metadata = null;
}
