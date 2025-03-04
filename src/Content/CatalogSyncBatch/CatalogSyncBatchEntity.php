<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\CatalogSyncBatch;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessEntity;

class CatalogSyncBatchEntity extends Entity
{
    use EntityIdTrait;

    protected string $catalogSyncProcessId;
    protected ?CatalogSyncProcessEntity $catalogSyncProcess;
    /** @var string[] */
    protected array $productIds = [];
    /** @var value-of<CatalogSyncBatchStatusEnum> */
    protected string $status;

    public function getCatalogSyncProcessId(): string
    {
        return $this->catalogSyncProcessId;
    }

    public function setCatalogSyncProcessId(string $catalogSyncProcessId): CatalogSyncBatchEntity
    {
        $this->catalogSyncProcessId = $catalogSyncProcessId;

        return $this;
    }

    public function getCatalogSyncProcess(): ?CatalogSyncProcessEntity
    {
        return $this->catalogSyncProcess;
    }

    public function setCatalogSyncProcess(?CatalogSyncProcessEntity $catalogSyncProcess): CatalogSyncBatchEntity
    {
        $this->catalogSyncProcess = $catalogSyncProcess;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getProductIds(): array
    {
        return $this->productIds;
    }

    /**
     * @param string[] $productIds
     *
     * @return $this
     */
    public function setProductIds(array $productIds): CatalogSyncBatchEntity
    {
        $this->productIds = $productIds;

        return $this;
    }

    /**
     * @return value-of<CatalogSyncBatchStatusEnum>
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param value-of<CatalogSyncBatchStatusEnum> $status
     *
     * @return $this
     */
    public function setStatus(string $status): CatalogSyncBatchEntity
    {
        $this->status = $status;

        return $this;
    }
}
