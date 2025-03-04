<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\PaymentMethod;

interface PaymentMethodInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getDescription(): string;

    public function getPaymentHandler(): string;

    /**
     * @return array<mixed>
     */
    public function getTranslations(): array;

    public function getPosition(): int;

    public function getTechnicalName(): string;
}
