<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Common;

class ProductReferenceCollection implements \Countable
{
    /**
     * @var ProductReference[]
     */
    private array $productReferences = [];

    public function add(ProductReference $product): void
    {
        if (in_array($product, $this->productReferences, true)) {
            return;
        }

        $this->productReferences[] = $product;
    }

    public function remove(ProductReference $productToRemove): void
    {
        if (in_array($productToRemove, $this->productReferences, true)) {
            return;
        }

        $this->productReferences = array_filter($this->productReferences, static function (ProductReference $product) use ($productToRemove) {
            return $product !== $productToRemove;
        });
    }

    /**
     * @return ProductReference[]
     */
    public function getProductReferences(): array
    {
        return $this->productReferences;
    }

    public function count(): int
    {
        return count($this->productReferences);
    }
}
