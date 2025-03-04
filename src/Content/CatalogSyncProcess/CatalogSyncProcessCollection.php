<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\CatalogSyncProcess;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<CatalogSyncProcessEntity>
 */
class CatalogSyncProcessCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CatalogSyncProcessEntity::class;
    }
}
