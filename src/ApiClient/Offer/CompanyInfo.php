<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class CompanyInfo implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public PostalAddress $billingAddress;

    public string $name;

    public ?string $taxNumber = null;

    public ?string $vatNumber = null;
}
