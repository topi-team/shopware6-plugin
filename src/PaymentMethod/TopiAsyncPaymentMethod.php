<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\PaymentMethod;

use TopiPaymentIntegration\Installer\PaymentMethodInstaller;
use TopiPaymentIntegration\PaymentHandler\TopiAsyncPaymentHandler;

class TopiAsyncPaymentMethod extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'Invoice (credit limit)';

    protected string $description = 'Pay by invoice within your credit limit';

    protected string $paymentHandler = TopiAsyncPaymentHandler::class;

    /**
     * @var array<mixed>
     */
    protected array $translations = [
        'de-DE' => [
            'name' => 'Rechnung (Kreditlimit)',
            'description' => 'Zahle auf Rechnung innerhalb des Kreditlimits',
        ],
        'en-GB' => [
            'name' => 'Invoice (credit limit)',
            'description' => 'Pay by invoice within your credit limit',
        ],
    ];

    protected int $position = 100;

    protected string $technicalName = 'topi_payment_integration-async_payment';
}
