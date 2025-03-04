<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\CatalogSyncBatch;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<CatalogSyncBatchEntity>
 */
class CatalogSyncBatchCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CatalogSyncBatchEntity::class;
    }
}
