<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\CatalogSyncBatch;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessEntity;

/**
 * @phpstan-type CatalogSyncBatchItemIdentifierType 'product'|'swp-product-option'
 * @phpstan-type CatalogSyncBatchItemIdentifier array{type: CatalogSyncBatchItemIdentifierType, id: string}
 */
class CatalogSyncBatchEntity extends Entity
{
    use EntityIdTrait;

    public const ITEM_TYPE_PRODUCT = 'product';
    public const ITEM_TYPE_SWP_PRODUCT_OPTION = 'swp-product-option';

    protected string $catalogSyncProcessId;
    protected ?CatalogSyncProcessEntity $catalogSyncProcess;
    /** @var CatalogSyncBatchItemIdentifier[] */
    protected array $itemIdentifiers = [];
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
     * @return CatalogSyncBatchItemIdentifier[]
     */
    public function getItemIdentifiers(): array
    {
        return $this->itemIdentifiers;
    }

    /**
     * @param CatalogSyncBatchItemIdentifier[] $itemIdentifiers
     *
     * @return $this
     */
    public function setItemIdentifiers(array $itemIdentifiers): CatalogSyncBatchEntity
    {
        $this->itemIdentifiers = $itemIdentifiers;

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
