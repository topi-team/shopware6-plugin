<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Installer;

use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class PluginInstaller implements InstallerInterface
{
    private PaymentMethodInstaller $paymentMethodInstallationService;
    private DatabaseInstaller $databaseInstallationService;

    public function __construct(
        PaymentMethodInstaller $paymentMethodInstallationService,
        DatabaseInstaller $databaseInstallationService,
    ) {
        $this->paymentMethodInstallationService = $paymentMethodInstallationService;
        $this->databaseInstallationService = $databaseInstallationService;
    }

    public function install(InstallContext $installContext): void
    {
        $this->paymentMethodInstallationService->install($installContext);
        $this->databaseInstallationService->install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->paymentMethodInstallationService->uninstall($uninstallContext);
        $this->databaseInstallationService->uninstall($uninstallContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->paymentMethodInstallationService->activate($activateContext);
        $this->databaseInstallationService->activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->paymentMethodInstallationService->deactivate($deactivateContext);
        $this->databaseInstallationService->deactivate($deactivateContext);
    }
}
