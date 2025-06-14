<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Installer;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchDefinition;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessDefinition;

readonly class DatabaseInstaller implements InstallerInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function install(InstallContext $installContext): void
    {
        // nothing to do
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        // ignore the $uninstallContext->keepUserData() as we only have tables to support processes
        $tables = [
            CatalogSyncBatchDefinition::ENTITY_NAME,
            CatalogSyncProcessDefinition::ENTITY_NAME,
        ];

        foreach ($tables as $table) {
            $this->connection->executeStatement(sprintf('DROP TABLE `%s`', $table));
        }
    }

    public function activate(ActivateContext $activateContext): void
    {
        // nothing to do
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        // nothing to do
    }

    public function update(UpdateContext $updateContext): void
    {
        // nothing to do
    }
}
