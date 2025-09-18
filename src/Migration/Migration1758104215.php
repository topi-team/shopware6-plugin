<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1758104215 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1758104215;
    }

    public function update(Connection $connection): void
    {
        $query = <<<'SQL'
            ALTER TABLE topi_catalog_sync_batch CHANGE product_ids item_identifiers JSON NOT NULL;            
SQL;

        $connection->executeStatement($query);
    }
}
