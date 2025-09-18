<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use TopiPaymentIntegration\ApiClient\Catalog\Image;
use TopiPaymentIntegration\ApiClient\Catalog\MoneyAmountWithOptionalTax;
use TopiPaymentIntegration\ApiClient\Catalog\Product;
use TopiPaymentIntegration\ApiClient\Common\ProductReference;

class SwpOptionToTopiProductConverter
{
    public function convert(object $option, SalesChannelEntity $salesChannel): Product
    {
        $product = new Product();

        $translated = method_exists($option, 'getTranslated') ? (array) $option->getTranslated() : [];
        $product->title = (string) ($translated['name'] ?? $this->getString($option, 'getName'));
        $product->subtitle = (string) ($translated['shortDescription'] ?? '');
        $long = (string) ($translated['longDescription'] ?? '');
        $product->description = mb_substr($long, 0, 1500);
        $product->descriptionLines = '' !== $long ? array_map(static fn ($l) => mb_substr($l, 0, 1500), explode("\n", $long)) : [];

        $currency = $salesChannel->getCurrency();
        assert($currency instanceof CurrencyEntity);

        $price = new MoneyAmountWithOptionalTax();
        // Best-effort fixed price: try to extract any price for the sales channel currency; fallback to 0
        $priceValues = $this->extractPriceForCurrency($option, $currency->getIsoCode());
        $price->net = (int) round(($priceValues['net'] ?? 0.0) * 100);
        $price->gross = (int) round(($priceValues['gross'] ?? 0.0) * 100);
        $price->currency = $currency->getIsoCode();
        $price->taxRate = (int) ($this->getNumeric($option, 'getTaxRate') ?? 0);
        $product->price = $price;

        // Optional media
        $url = $this->extractFirstMediaUrl($option);
        if ($url) {
            $image = new Image();
            $image->url = $url;
            $product->image = $image;
        }

        // Identifiers
        $ref = new ProductReference();
        $ref->source = 'swp-option-id';
        $ref->reference = (string) $this->getString($option, 'getId');
        $product->sellerProductReferences[] = $ref;

        $product->isActive = true;

        return $product;
    }

    private function extractPriceForCurrency(object $option, string $iso): array
    {
        // Try generic getPrice() API with currency-specific access
        if (method_exists($option, 'getPrice')) {
            $p = $option->getPrice();
            // Try Shopware price collection-like API
            if ($p && method_exists($p, 'first')) {
                $first = $p->first();
                if ($first && method_exists($first, 'getCurrencyId')) {
                    // Price items may carry gross/net; but we don't have currency map, so just take first
                    $gross = method_exists($first, 'getGross') ? (float) $first->getGross() : null;
                    $net = method_exists($first, 'getNet') ? (float) $first->getNet() : null;
                    if (null !== $gross || null !== $net) {
                        return ['gross' => $gross ?? 0.0, 'net' => $net ?? 0.0];
                    }
                }
            }
        }

        return ['gross' => 0.0, 'net' => 0.0];
    }

    private function extractFirstMediaUrl(object $option): ?string
    {
        if (!method_exists($option, 'getMedia')) {
            return null;
        }
        $mediaAssoc = $option->getMedia();
        if (!$mediaAssoc || !method_exists($mediaAssoc, 'first')) {
            return null;
        }
        $first = $mediaAssoc->first();
        if ($first && method_exists($first, 'getMedia')) {
            $media = $first->getMedia();
            if ($media && method_exists($media, 'getUrl')) {
                return (string) $media->getUrl();
            }
        }

        return null;
    }

    private function getString(object $obj, string $method): string
    {
        return method_exists($obj, $method) ? (string) $obj->$method() : '';
    }

    private function getNumeric(object $obj, string $method): ?float
    {
        return method_exists($obj, $method) ? (float) $obj->$method() : null;
    }
}
