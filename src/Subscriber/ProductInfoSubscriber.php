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
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
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
        if ($event instanceof CartEvent) {
            $cart = $event->getCart();
        }

        if (!isset($cart)) {
            $cart = $event->getPage()->getCart();
        }

        foreach ($cart->getLineItems() as $lineItem) {
            if (LineItem::PRODUCT_LINE_ITEM_TYPE !== $lineItem->getType()) {
                continue;
            }

            $lineItem->addExtension(
                ProductExtension::EXTENSION_NAME,
                $this->lineItemToProductExtension($lineItem, $event->getSalesChannelContext()->getSalesChannel()),
            );
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
                $this->swProductToProductExtension($product, $event->getSalesChannelContext()->getSalesChannel()),
            );
        }
    }

    private function lineItemToProductExtension(LineItem $lineItem, SalesChannelEntity $salesChannel): ProductExtension
    {
        $currency = $salesChannel->getCurrency();
        assert($currency instanceof CurrencyEntity);

        $price = new MoneyAmountWithOptionalTax();
        $unitPrice = $lineItem->getPrice()?->getUnitPrice();
        $calculatedTaxes = $lineItem->getPrice()?->getCalculatedTaxes();

        $net = $unitPrice - ($calculatedTaxes->getAmount() / $lineItem->getQuantity());
        $gross = $calculatedTaxes->count() === 0
            ? $unitPrice * (1 + ($calculatedTaxes->first()?->getTaxRate() ?? 19.0) / 100)
            : $unitPrice;

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

    private function swProductToProductExtension(SalesChannelProductEntity $product, SalesChannelEntity $salesChannel): ProductExtension
    {
        $currency = $salesChannel->getCurrency();
        assert($currency instanceof CurrencyEntity);

        $price = new MoneyAmountWithOptionalTax();
        $calculatedPrice = $product->getCalculatedCheapestPrice();

        $taxRate = (int) ($product->getTax()?->getTaxRate() ?? 19.0);

        $totalPrice = $calculatedPrice->getTotalPrice();

        $net = $totalPrice - $calculatedPrice->getCalculatedTaxes()->getAmount();
        $gross = $calculatedPrice->getCalculatedTaxes()->count() === 0
            ? $totalPrice * (1 + $taxRate / 100)
            : $totalPrice;

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
