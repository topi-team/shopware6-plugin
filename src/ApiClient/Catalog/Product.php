<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\Common\MoneyAmount;
use TopiPaymentIntegration\ApiClient\Common\ProductReference;
use TopiPaymentIntegration\ApiClient\JsonSerializeLowerSnakeCaseTrait;

class Product implements \JsonSerializable
{
    use JsonSerializeLowerSnakeCaseTrait;

    public string $description;

    /**
     * @var string[]
     */
    public array $descriptionLines;

    /**
     * @var ExtraProductDetails[]
     */
    public array $extraDetails = [];

    public ?Image $image = null;

    public bool $isActive;

    /**
     * @var MoneyAmountWithOptionalTax|null
     */
    public ?MoneyAmount $price = null;

    public ?string $manufacturer = null;

    /**
     * @var ProductIdentifier[]
     */
    public array $productStandardIdentifiers = [];

    /**
     * @var Category[]
     */
    public array $sellerCategories = [];

    /**
     * @var ProductReference[]
     */
    public array $sellerProductReferences = [];

    public ?string $sellerProductType = null;

    public ?string $shopProductDescriptionUrl = null;

    public ?string $subtitle = null;

    public string $title;
}
