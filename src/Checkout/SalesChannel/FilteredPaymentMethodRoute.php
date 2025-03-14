<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Checkout\SalesChannel;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use TopiPaymentIntegration\ApiClient\Factory\EnvironmentFactory;
use TopiPaymentIntegration\Exception\InvalidEnvironmentException;
use TopiPaymentIntegration\Service\CartAvailabilityService;
use TopiPaymentIntegration\Service\PaymentMethodAvailabilityService;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class FilteredPaymentMethodRoute extends AbstractPaymentMethodRoute
{
    public function __construct(
        private readonly AbstractPaymentMethodRoute $decorated,
        private readonly CartService $cartService,
        private readonly PaymentMethodAvailabilityService $paymentMethodAvailabilityService,
        private readonly EnvironmentFactory $environmentFactory,
        private readonly CartAvailabilityService $cartAvailabilityService,
    ) {
    }

    public function getDecorated(): AbstractPaymentMethodRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/payment-method', name: 'store-api.payment.method', defaults: ['_entity' => 'payment_method'], methods: ['GET', 'POST'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        $response = $this->getDecorated()->load($request, $context, $criteria);

        if (!$request->query->getBoolean('onlyAvailable') && !$request->request->getBoolean('onlyAvailable')) {
            return $response;
        }

        $paymentMethod = $this->paymentMethodAvailabilityService->getTopiPaymentMethod(
            $context,
            $context->getContext(),
        );

        if (!$paymentMethod instanceof PaymentMethodEntity) {
            return $response;
        }

        if (!$this->hasPaymentMethod($response->getPaymentMethods(), $paymentMethod)) {
            return $response;
        }

        try {
            $this->environmentFactory->makeEnvironment($context->getSalesChannelId());
        } catch (InvalidEnvironmentException $e) {
            $this->removePaymentMethod($response->getPaymentMethods(), $paymentMethod);

            return $response;
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);
        if ($this->isZeroValueCart($cart)) {
            $this->removePaymentMethod($response->getPaymentMethods(), $paymentMethod);

            return $response;
        }

        if (!$this->cartAvailabilityService->isCartAvailableForPurchaseThroughTopi(
            $cart->getLineItems(),
            $context->getSalesChannelId()
        )) {
            $this->removePaymentMethod($response->getPaymentMethods(), $paymentMethod);

            return $response;
        }

        return $response;
    }

    private function hasPaymentMethod(
        PaymentMethodCollection $paymentMethods,
        PaymentMethodEntity $paymentMethodEntity,
    ): bool {
        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->getId() === $paymentMethodEntity->getId()) {
                return true;
            }
        }

        return false;
    }

    private function removePaymentMethod(
        PaymentMethodCollection $paymentMethods,
        PaymentMethodEntity $paymentMethodEntity,
    ): void {
        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->getId() === $paymentMethodEntity->getId()) {
                $paymentMethods->remove($paymentMethod->getId());
            }
        }
    }

    private function isZeroValueCart(Cart $cart): bool
    {
        if (0 === $cart->getLineItems()->count()) {
            return false;
        }

        if ($cart->getPrice()->getTotalPrice() > 0) {
            return false;
        }

        return true;
    }
}
