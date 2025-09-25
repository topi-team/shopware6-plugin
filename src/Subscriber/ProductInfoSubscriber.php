<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CartEvent;
use Shopware\Core\Checkout\Cart\Event\CartLoadedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TopiPaymentIntegration\ApiClient\Catalog\MoneyAmountWithOptionalTax;
use TopiPaymentIntegration\ApiClient\Common\ProductReference;
use TopiPaymentIntegration\Content\Extension\ProductExtension;

readonly class ProductInfoSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            OffcanvasCartPageLoadedEvent::class => 'addCartExtension',
            CheckoutConfirmPageLoadedEvent::class => 'addCartExtension',
            CartLoadedEvent::class => 'addCartExtension',
            'sales_channel.product.search.result.loaded' => 'addProductSearchResultExtension',
        ];
    }

    public function addCartExtension(
        OffcanvasCartPageLoadedEvent
        |CheckoutConfirmPageLoadedEvent
        |CartLoadedEvent $event,
    ): void {
        if ((interface_exists(CartEvent::class) && $event instanceof CartEvent) || $event instanceof CartLoadedEvent) {
            $cart = $event->getCart();
        }

        if (!isset($cart)) {
            $cart = $event->getPage()->getCart();
        }

        foreach ($cart->getLineItems() as $lineItem) {
            $type = $lineItem->getType();

            if (LineItem::PRODUCT_LINE_ITEM_TYPE === $type) {
                $lineItem->addExtension(
                    ProductExtension::EXTENSION_NAME,
                    $this->lineItemToProductExtension($lineItem, $event->getSalesChannelContext()),
                );
                continue;
            }

            // Support SWP Product Options wrapper: attach extension to wrapper using child product data
            if ('product-with-options' === $type) {
                // Use the wrapper line item's calculated price which includes selected options
                $lineItem->addExtension(
                    ProductExtension::EXTENSION_NAME,
                    $this->lineItemToProductExtension($lineItem, $event->getSalesChannelContext()),
                );
            }
        }
    }

    /**
     * @param SalesChannelEntitySearchResultLoadedEvent<SalesChannelProductCollection> $event
     */
    public function addProductSearchResultExtension(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        /** @var SalesChannelProductEntity $product */
        foreach ($event->getResult() as $product) {
            $product->addExtension(
                ProductExtension::EXTENSION_NAME,
                $this->swProductToProductExtension($product, $event->getSalesChannelContext()),
            );
        }
    }

    private function lineItemToProductExtension(LineItem $lineItem, SalesChannelContext $salesChannelContext): ProductExtension
    {
        $salesChannel = $salesChannelContext->getSalesChannel();
        $currency = $salesChannel->getCurrency();
        assert($currency instanceof CurrencyEntity);

        $price = new MoneyAmountWithOptionalTax();
        $unitPrice = $lineItem->getPrice()?->getUnitPrice();
        $calculatedTaxes = $lineItem->getPrice()?->getCalculatedTaxes();

        // Check if customer group displays gross prices
        $displayGross = $salesChannelContext->getCurrentCustomerGroup()->getDisplayGross();

        if ($displayGross) {
            // Customer sees gross prices - unitPrice is gross
            $gross = $unitPrice;
            $net = $unitPrice - ($calculatedTaxes->getAmount() / $lineItem->getQuantity());
        } else {
            // Customer sees net prices - unitPrice is net
            $net = $unitPrice;
            $taxRate = $calculatedTaxes->first()?->getTaxRate() ?? 19.0;
            $gross = $unitPrice * (1 + $taxRate / 100);
        }

        $price->gross = (int) round($gross * 100);
        $price->net = (int) round($net * 100);
        $price->taxRate = (int) ($calculatedTaxes->first()?->getTaxRate() ?? 19.0);
        $price->currency = $currency->getIsoCode();

        $shopwareIdReference = new ProductReference();
        $shopwareIdReference->source = 'shopware-ids';
        $shopwareIdReference->reference = $lineItem->getReferencedId();

        return new ProductExtension(
            $price,
            $shopwareIdReference,
            $lineItem->getQuantity(),
        );
    }

    private function swProductToProductExtension(SalesChannelProductEntity $product, SalesChannelContext $salesChannelContext): ProductExtension
    {
        $salesChannel = $salesChannelContext->getSalesChannel();
        $currency = $salesChannel->getCurrency();
        assert($currency instanceof CurrencyEntity);

        $price = new MoneyAmountWithOptionalTax();
        $calculatedPrice = $product->getCalculatedCheapestPrice();

        $taxRate = (int) ($product->getTax()?->getTaxRate() ?? 19.0);
        $totalPrice = $calculatedPrice->getTotalPrice();

        // Check if customer group displays gross prices
        $displayGross = $salesChannelContext->getCurrentCustomerGroup()->getDisplayGross();

        if ($displayGross) {
            // Customer sees gross prices - totalPrice is gross
            $gross = $totalPrice;
            $net = $totalPrice - $calculatedPrice->getCalculatedTaxes()->getAmount();
        } else {
            // Customer sees net prices - totalPrice is net
            $net = $totalPrice;
            $gross = $totalPrice * (1 + $taxRate / 100);
        }

        $price->net = (int) ($net * 100);
        $price->gross = (int) ($gross * 100);
        $price->currency = $currency->getIsoCode();
        $price->taxRate = $taxRate;

        $shopwareIdReference = new ProductReference();
        $shopwareIdReference->source = 'shopware-ids';
        $shopwareIdReference->reference = $product->getId();

        return new ProductExtension(
            $price,
            $shopwareIdReference,
            $product->getMinPurchase() ?? 1,
        );
    }
}
