<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\PaymentHandler;

use Shopware\Core\Checkout\Payment\Cart\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use TopiPaymentIntegration\Service\TopiPaymentProcessor;

readonly class TopiAsyncPaymentHandlerLegacy implements AsynchronousPaymentHandlerInterface
{
    public function __construct(private TopiPaymentProcessor $processor)
    {
    }

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext,
    ): RedirectResponse {
        return $this->processor->startPayment(
            $transaction->getOrderTransaction()->getId(),
            $transaction->getReturnUrl(),
            $salesChannelContext->getContext()
        );
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext,
    ): void {
        // no need to do anything when the customer returns
    }
}

