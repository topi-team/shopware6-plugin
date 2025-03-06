<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\PaymentMethod;

use TopiPaymentIntegration\Installer\PaymentMethodInstaller;
use TopiPaymentIntegration\PaymentHandler\TopiAsyncPaymentHandler;

class TopiAsyncPaymentMethod extends AbstractPaymentMethod
{
    public const UUID = PaymentMethodInstaller::PAYMENT_METHOD_IDS[self::class];

    protected string $id = self::UUID;

    protected string $name = 'Rent with topi';

    protected string $description = 'Your effortless IT subscription for every business. Optimize your cash flow by renting your IT hardware at low monthly rates. Its flexible, fast, and fully digital.';

    protected string $paymentHandler = TopiAsyncPaymentHandler::class;

    /**
     * @var array<mixed>
     */
    protected array $translations = [
        'de-DE' => [
            'name' => 'Mieten mit topi',
            'description' => 'Die unkomplizierte IT-Miete für jedes Business. Optimieren Sie Ihren Cashflow und mieten Sie Ihre IT-Hardware zu niedrigen monatlichen Raten. Flexibel, schnell und komplett digital.',
        ],
        'en-GB' => [
            'name' => 'Rent with topi',
            'description' => 'Your effortless IT subscription for every business. Optimize your cash flow by renting your IT hardware at low monthly rates. Its flexible, fast, and fully digital.',
        ],
    ];

    protected int $position = 100;

    protected string $technicalName = 'topi_payment_integration-async_payment';
}
