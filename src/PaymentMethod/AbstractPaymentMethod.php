<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\PaymentMethod;

abstract class AbstractPaymentMethod implements PaymentMethodInterface
{
    protected string $id;

    protected string $name;

    protected string $description;

    protected string $paymentHandler;

    protected string $technicalName;

    /**
     * @var array<mixed>
     */
    protected array $translations;

    protected int $position;

    protected array $media;

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPaymentHandler(): string
    {
        return $this->paymentHandler;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    /**
     * @return array{
     *     id: string,
     *
     * }
     */
    public function getMedia(): array
    {
        return $this->media;
    }
}
