<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Offer;

use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class CreateOfferData extends BaseOffer implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public string $exitRedirect;

    public string $expiresAt;

    public string $successRedirect;
}
