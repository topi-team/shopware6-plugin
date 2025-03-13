<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CartEvent;
use Shopware\Core\Checkout\Cart\Event\CartLoadedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
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
                $this->lineItemToProductExtension($lineItem, $event->getSalesChannelContext()->getSalesChannel())
            );
        }
    }

    public function addProductSearchResultExtension(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        foreach ($event->getResult() as $product) {
            $product->addExtension(
                ProductExtension::EXTENSION_NAME,
                $this->swProductToProductExtension($product, $event->getSalesChannelContext()->getSalesChannel())
            );
        }
    }

    private function lineItemToProductExtension(LineItem $lineItem, SalesChannelEntity $salesChannel): ProductExtension
    {
        $currency = $salesChannel->getCurrency();
        assert($currency instanceof CurrencyEntity);

        $price = new MoneyAmountWithOptionalTax();
        $price->gross = (int) round($lineItem->getPrice()?->getTotalPrice() * 100);
        $price->net = (int) round((($lineItem->getPrice()?->getTotalPrice() ?? 0.0)
                - ($lineItem->getPrice()?->getCalculatedTaxes()->getAmount() ?? 0.0)) * 100);
        $price->taxRate = (int) ($lineItem->getPrice()?->getCalculatedTaxes()->first()?->getTaxRate() ?? 19.0);
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
        $price->net = (int) (($product->getCurrencyPrice($currency->getId())?->getNet() ?? 0.0) * 100);
        $price->gross = (int) (($product->getCurrencyPrice($currency->getId())?->getGross() ?? 0.0) * 100);
        $price->currency = $currency->getIsoCode();
        $price->taxRate = (int) ($product->getTax()?->getTaxRate() ?? 19.0);

        $shopwareIdReference = new ProductReference();
        $shopwareIdReference->source = 'shopware-ids';
        $shopwareIdReference->reference = $product->getId();

        return new ProductExtension(
            $price,
            $shopwareIdReference,
            $product->getMinPurchase() ?? 1
        );
    }
}
