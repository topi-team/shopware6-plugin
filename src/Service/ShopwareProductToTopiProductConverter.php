<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Seo\SeoUrl\SeoUrlEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use TopiPaymentIntegration\ApiClient\Catalog\Category;
use TopiPaymentIntegration\ApiClient\Catalog\ExtraProductDetails;
use TopiPaymentIntegration\ApiClient\Catalog\MoneyAmountWithOptionalTax;
use TopiPaymentIntegration\ApiClient\Catalog\Product;
use TopiPaymentIntegration\ApiClient\Catalog\ProductIdentifier;
use TopiPaymentIntegration\ApiClient\Common\ProductReference;

class ShopwareProductToTopiProductConverter
{
    public function convert(ProductEntity $shopwareProduct, SalesChannelEntity $salesChannel): Product
    {
        $topiProduct = new Product();

        $topiProduct->title = $shopwareProduct->getTranslated()['name'];
        $topiProduct->subtitle = $shopwareProduct->getTranslated()['metaDescription'] ?? '';
        $description = $shopwareProduct->getTranslated()['description'] ?? '';
        $topiProduct->description = $description;
        $topiProduct->descriptionLines = explode("\n", $description);

        foreach ($shopwareProduct->getCategoriesRo() as $category) {
            $apiCategory = new Category();
            $apiCategory->name = $category->getName();
            $apiCategory->id = $category->getId();
            $apiCategory->parentCategoryId = (string) $category->getParentId();

            $topiProduct->sellerCategories[] = $apiCategory;
        }

        $topiProduct->isActive = $shopwareProduct->getActive() ?? false;

        $currency = $salesChannel->getCurrency();

        assert($currency instanceof CurrencyEntity);

        $price = new MoneyAmountWithOptionalTax();
        $price->net = (int) (($shopwareProduct->getCurrencyPrice($currency->getId())?->getNet() ?? 0.0) * 100);
        $price->gross = (int) (($shopwareProduct->getCurrencyPrice($currency->getId())?->getGross() ?? 0.0) * 100);
        $price->currency = $currency->getIsoCode();
        $price->taxRate = (int) ($shopwareProduct->getTax()?->getTaxRate() ?? 19.0);

        $topiProduct->price = $price;

        $topiProduct->manufacturer = $shopwareProduct->getManufacturer()?->getName() ?? '';
        $mpn = $shopwareProduct->getManufacturerNumber();
        if (!is_null($mpn)) {
            $supplierIdentifier = new ProductIdentifier();
            $supplierIdentifier->identifierType = 'MPN';
            $supplierIdentifier->id = $mpn;
            $topiProduct->productStandardIdentifiers[] = $supplierIdentifier;
        }

        $shopwareIdReference = new ProductReference();
        $shopwareIdReference->source = 'shopware-ids';
        $shopwareIdReference->reference = $shopwareProduct->getId();
        $topiProduct->sellerProductReferences[] = $shopwareIdReference;

        $shopwareOrdernumberReference = new ProductReference();
        $shopwareOrdernumberReference->source = 'shopware-ordernumbers';
        $shopwareOrdernumberReference->reference = $shopwareProduct->getProductNumber();
        $topiProduct->sellerProductReferences[] = $shopwareOrdernumberReference;

        foreach ($shopwareProduct->getProperties() as $property) {
            $extraProductDetail = new ExtraProductDetails();
            $extraProductDetail->property = $property->getGroup()?->getName() ?? 'UNKNOWN';
            $extraProductDetail->value = $property->getName();

            $topiProduct->extraDetails[] = $extraProductDetail;
        }

        $salesChannelUrl = $salesChannel->getDomains()?->first()?->getUrl();
        $topiProduct->shopProductDescriptionUrl = ($salesChannelUrl ?? '').'/'.$shopwareProduct->getSeoUrls()
            ?->filterBySalesChannelId($salesChannel->getId())
            ->filter(fn (SeoUrlEntity $entity) => $entity->getIsCanonical())->first()?->getSeoPathInfo();

        return $topiProduct;
    }
}
