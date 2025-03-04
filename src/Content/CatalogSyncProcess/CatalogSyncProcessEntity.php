<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\CatalogSyncProcess;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchCollection;

class CatalogSyncProcessEntity extends Entity
{
    use EntityIdTrait;

    protected string $salesChannelId;
    protected ?SalesChannelEntity $salesChannel;
    protected CatalogSyncBatchCollection $catalogSyncBatches;
    protected \DateTimeInterface $startDate;
    protected \DateTimeInterface $endDate;
    protected string $status;

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): CatalogSyncProcessEntity
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): CatalogSyncProcessEntity
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): CatalogSyncProcessEntity
    {
        $this->status = $status;

        return $this;
    }

    public function getCatalogSyncBatches(): CatalogSyncBatchCollection
    {
        return $this->catalogSyncBatches;
    }

    public function setCatalogSyncBatches(CatalogSyncBatchCollection $catalogSyncBatches): CatalogSyncProcessEntity
    {
        $this->catalogSyncBatches = $catalogSyncBatches;
        return $this;
    }
}
