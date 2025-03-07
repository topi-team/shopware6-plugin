<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Event;

use TopiPaymentIntegration\ApiClient\AppliesArrayDataInterface;

interface EventInterface extends AppliesArrayDataInterface
{
    public function getEvent(): string;

    public function setEvent(string $event): void;
}
