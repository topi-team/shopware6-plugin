<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Installer;

use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

interface InstallerInterface
{
    public function install(InstallContext $installContext): void;

    public function uninstall(UninstallContext $uninstallContext): void;

    public function activate(ActivateContext $activateContext): void;

    public function deactivate(DeactivateContext $deactivateContext): void;

    public function update(UpdateContext $updateContext): void;
}
