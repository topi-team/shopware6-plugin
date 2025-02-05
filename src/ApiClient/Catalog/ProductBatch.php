<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Catalog;

use TopiPaymentIntegration\ApiClient\Common\ProductReferenceCollection;

class ProductBatch implements \Countable
{
    /**
     * @var Product[]
     */
    private array $products = [];

    private int $lastId = 0;

    public function add(Product $product): void
    {
        if (in_array($product, $this->products, true)) {
            return;
        }

        $this->products[] = $product;
    }

    public function remove(Product $productToRemove): void
    {
        if (in_array($productToRemove, $this->products, true)) {
            return;
        }

        $this->products = array_filter($this->products, static function (Product $product) use ($productToRemove) {
            return $product !== $productToRemove;
        });
    }

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function count(): int
    {
        return count($this->products);
    }

    public function toProductReferenceCollection(): ProductReferenceCollection
    {
        $productReferenceCollection = new ProductReferenceCollection();
        foreach ($this->products as $product) {
            $productReferenceCollection->add(
                $product->sellerProductReferences[0]
            );
        }

        return $productReferenceCollection;
    }

    public function registerDetailId(int $id): void
    {
        if ($id > $this->lastId) {
            $this->lastId = $id;
        }
    }

    public function getLastId(): int
    {
        return $this->lastId;
    }
}
