<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class CustomerInfo implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public CompanyInfo $company;

    public ?string $customerGroup = null;

    public string $email;

    public ?string $fullName = null;
}
