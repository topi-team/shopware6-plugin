<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class PostalAddress implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public string $city;

    public string $countryCode;

    public string $line1;

    public ?string $line2 = null;

    public string $postalCode;

    public ?string $region = null;
}
