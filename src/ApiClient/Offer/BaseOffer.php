<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

class BaseOffer
{
    public CustomerInfo $customer;

    /**
     * @var OfferLinePayload[]
     */
    public array $lines = [];

    /**
     * @var array<string,string>|null
     */
    public ?array $metadata = null;

    public string $salesChannel = 'ecommerce';

    public string $sellerOfferReference;

    public ShippingInfo $shipping;

    public PostalAddress $shippingAddress;
}
