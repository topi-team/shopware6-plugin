<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use TopiPaymentIntegration\Service\TopiPaymentProcessor;

readonly class TopiPaymentHandlerFactory
{
    public function __construct(private TopiPaymentProcessor $processor)
    {
    }

    /**
     * Returns a version-appropriate payment handler instance.
     * - >= 6.6.5.0: TopiAsyncPaymentHandler66 (AbstractPaymentHandler based)
     * - <  6.6.5.0: TopiAsyncPaymentHandlerLegacy (AsynchronousPaymentHandlerInterface based)
     */
    public function create(): object
    {
        if (class_exists(AbstractPaymentHandler::class)) {
            return new TopiAsyncPaymentHandler66($this->processor);
        }

        return new TopiAsyncPaymentHandlerLegacy($this->processor);
    }
}

