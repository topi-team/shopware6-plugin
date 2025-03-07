<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Event;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataTrait;
use TopiPaymentIntegration\ApiClient\Offer\CreatedOffer;

class OfferEvent implements EventInterface
{
    use AppliesArrayDataTrait;

    public CreatedOffer $offer;

    /**
     * @var 'offer.voided'|'offer.accepted'|'offer.expired'
     */
    public string $event = 'offer.*';

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): void
    {
        $this->event = $event;
    }
}
