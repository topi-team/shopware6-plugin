<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Component\HttpFoundation\RedirectResponse;
use TopiPaymentIntegration\ApiClient\Client;
use TopiPaymentIntegration\ApiClient\Common\MoneyAmount;
use TopiPaymentIntegration\ApiClient\Common\ProductReference;
use TopiPaymentIntegration\ApiClient\Factory\EnvironmentFactory;
use TopiPaymentIntegration\ApiClient\Offer\CompanyInfo;
use TopiPaymentIntegration\ApiClient\Offer\CreateOfferData;
use TopiPaymentIntegration\ApiClient\Offer\CustomerInfo;
use TopiPaymentIntegration\ApiClient\Offer\OfferLinePayload;
use TopiPaymentIntegration\ApiClient\Offer\PostalAddress;
use TopiPaymentIntegration\ApiClient\Offer\ShippingInfo;

readonly class TopiPaymentProcessor
{
    /**
     * @param EntityRepository<OrderTransactionCollection> $orderTransactionRepository
     */
    public function __construct(
        private EntityRepository $orderTransactionRepository,
        private Client $client,
        private EnvironmentFactory $environmentFactory,
    ) {
    }

    public function startPayment(string $orderTransactionId, string $returnUrl, Context $context): RedirectResponse
    {
        $orderTransaction = $this->getOrderTransaction($orderTransactionId, $context);
        $order = $orderTransaction->getOrder();
        if (!$order) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        $offer = new CreateOfferData();
        foreach ($order->getLineItems() as $shopwareLineItem) {
            if (LineItem::PRODUCT_LINE_ITEM_TYPE !== $shopwareLineItem->getType()) {
                throw PaymentException::invalidOrder($order->getId());
            }

            $lineItem = new OfferLinePayload();
            $lineItem->title = $shopwareLineItem->getLabel();
            $lineItem->quantity = $shopwareLineItem->getQuantity();
            $price = new MoneyAmount();
            $price->currency = $order->getCurrency()?->getIsoCode();

            $price->gross = (int) round($shopwareLineItem->getTotalPrice() * 100);
            $price->net = (int) round((($shopwareLineItem->getPrice()?->getTotalPrice() ?? 0.0)
                - ($shopwareLineItem->getPrice()?->getCalculatedTaxes()->getAmount() ?? 0.0)) * 100);
            $lineItem->price = $price;

            $productReference = new ProductReference();
            $productReference->source = 'shopware-ids';
            $productReference->reference = $shopwareLineItem->getReferencedId();
            $lineItem->sellerProductReference = $productReference;

            $offer->lines[] = $lineItem;
        }

        $customerInfo = new CustomerInfo();
        $shopwareBillingAddress = $order->getBillingAddress();

        $orderCustomer = $order->getOrderCustomer();

        $customerInfo->fullName = trim(($shopwareBillingAddress?->getFirstName() ?? '')
            .' '.($shopwareBillingAddress?->getLastName() ?? ''));
        $customerInfo->customerGroup = $orderCustomer?->getCustomer()?->getGroup()?->getName() ?? 'UNKNOWN';
        $customerInfo->email = $orderCustomer?->getEmail();

        $customerCompany = new CompanyInfo();
        $customerCompany->name = $orderCustomer?->getCompany()
            ?? $shopwareBillingAddress?->getCompany()
            ?? $customerInfo->fullName;
        $customerCompany->vatNumber = ($orderCustomer?->getVatIds() ?? [null])[0] ?? $shopwareBillingAddress?->getVatId();

        $billingAddress = new PostalAddress();
        $billingAddress->city = $shopwareBillingAddress?->getCity() ?? '';
        $billingAddress->postalCode = $shopwareBillingAddress?->getZipcode() ?? '';
        $billingAddress->countryCode = $shopwareBillingAddress?->getCountry()?->getIso() ?? '';
        $billingAddress->line1 = $shopwareBillingAddress?->getStreet() ?? '';

        if ($line2 = $shopwareBillingAddress?->getAdditionalAddressLine1()) {
            $billingAddress->line2 = $line2;
        }

        $customerCompany->billingAddress = $billingAddress;
        $customerInfo->company = $customerCompany;
        $offer->customer = $customerInfo;

        $shopwareShippingAddress = $order->getDeliveries()?->first()?->getShippingOrderAddress();
        $shippingAddress = new PostalAddress();
        $shippingAddress->city = $shopwareShippingAddress?->getCity() ?? '';
        $shippingAddress->postalCode = $shopwareShippingAddress?->getZipcode() ?? '';
        $shippingAddress->countryCode = $shopwareShippingAddress?->getCountry()?->getIso() ?? '';
        $shippingAddress->line1 = $shopwareShippingAddress?->getStreet() ?? '';
        $offer->shippingAddress = $shippingAddress;

        $shippingInfo = new ShippingInfo();
        $shippingPrice = new MoneyAmount();
        $shippingPrice->currency = $order->getCurrency()?->getIsoCode();
        $shippingPrice->net = (int) (($order->getShippingCosts()->getTotalPrice() - $order->getShippingCosts()->getCalculatedTaxes()->getAmount()) * 100);
        $shippingPrice->gross = (int) ($order->getShippingCosts()->getTotalPrice() * 100);
        $shippingInfo->price = $shippingPrice;
        $shippingInfo->sellerShippingReference = $order->getDeliveries()?->first()?->getShippingMethod()?->getId() ?? 'UNKNOWN';
        $offer->shipping = $shippingInfo;

        $offer->expiresAt = (new \DateTime())->add(new \DateInterval('P1D'))->format('c');
        $offer->sellerOfferReference = $orderTransactionId;
        $offer->successRedirect = $returnUrl;
        $offer->exitRedirect = $returnUrl;

        try {
            $createOffer = $this->client->offer(
                $this->environmentFactory->makeEnvironment($order->getSalesChannelId())
            )->createOffer($offer);
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted($orderTransactionId, 'An error occurred during the communication with external payment gateway'.PHP_EOL.$e->getMessage());
        }

        return new RedirectResponse($createOffer->checkoutRedirectUrl);
    }

    private function getOrderTransaction(string $orderTransactionId, Context $context): OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order.orderCustomer.customer');
        $criteria->addAssociation('order.orderCustomer.customer.group');
        $criteria->addAssociation('order.orderCustomer.salutation');
        $criteria->addAssociation('order.language');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('order.deliveries.shippingMethod');
        $criteria->addAssociation('order.billingAddress.country');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('order.transactions.stateMachineState');
        $criteria->addAssociation('order.transactions.paymentMethod.appPaymentMethod.app');
        $criteria->addAssociation('stateMachineState');
        $criteria->addAssociation('paymentMethod.appPaymentMethod.app');
        $criteria->getAssociation('order.transactions')->addSorting(new FieldSorting('createdAt'));
        $criteria->addSorting(new FieldSorting('createdAt'));

        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->getEntities()->first();

        if (!$orderTransaction) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        return $orderTransaction;
    }
}

