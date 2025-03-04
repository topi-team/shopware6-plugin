<?php declare(strict_types=1);

namespace TopiPaymentIntegration\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('topi_payment_integration')]
class Migration1741107209TopiCatalogSyncProcess extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1741107209;
    }

    public function update(Connection $connection): void
    {
        $query = <<<'SQL'
            CREATE TABLE topi_catalog_sync_process (id BINARY(16) NOT NULL, sales_channel_id BINARY(16) DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
SQL;

        $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Add destructive update if necessary
    }
}
