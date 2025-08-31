<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use TopiPaymentIntegration\Service\TopiPaymentProcessor;

class TopiAsyncPaymentHandler66 extends AbstractPaymentHandler
{
    public function __construct(private readonly TopiPaymentProcessor $processor)
    {
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct,
    ): ?RedirectResponse {
        return $this->processor->startPayment(
            $transaction->getOrderTransactionId(),
            $transaction->getReturnUrl(),
            $context
        );
    }

    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void
    {
        // no need to do anything when the customer returns
    }
}

