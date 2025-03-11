<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Installer;

use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

readonly class PluginInstaller implements InstallerInterface
{
    public function __construct(
        private PaymentMethodInstaller $paymentMethodInstallationService,
        private DatabaseInstaller $databaseInstallationService,
        private CustomFieldInstaller $customFieldInstaller,
    ) {
    }

    public function install(InstallContext $installContext): void
    {
        $this->paymentMethodInstallationService->install($installContext);
        $this->databaseInstallationService->install($installContext);
        $this->customFieldInstaller->install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->paymentMethodInstallationService->uninstall($uninstallContext);
        $this->databaseInstallationService->uninstall($uninstallContext);
        $this->customFieldInstaller->uninstall($uninstallContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->paymentMethodInstallationService->activate($activateContext);
        $this->databaseInstallationService->activate($activateContext);
        $this->customFieldInstaller->activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->paymentMethodInstallationService->deactivate($deactivateContext);
        $this->databaseInstallationService->deactivate($deactivateContext);
        $this->customFieldInstaller->deactivate($deactivateContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->paymentMethodInstallationService->update($updateContext);
        $this->databaseInstallationService->update($updateContext);
        $this->customFieldInstaller->update($updateContext);
    }
}
