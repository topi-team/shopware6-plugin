<?php

declare(strict_types=1);

namespace TopiPaymentIntegration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderCollection;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use TopiPaymentIntegration\Installer\CustomFieldInstaller;
use TopiPaymentIntegration\Installer\DatabaseInstaller;
use TopiPaymentIntegration\Installer\MediaInstaller;
use TopiPaymentIntegration\Installer\PaymentMethodInstaller;
use TopiPaymentIntegration\Installer\PluginInstaller;

class TopiPaymentIntegrationPlugin extends Plugin
{
    public const CATALOG_SYNC_BATCH_SIZE = 250;

    public static function getPluginDir(): string
    {
        return dirname(__DIR__);
    }

    public function executeComposerCommands(): bool
    {
        return true;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $locator = new FileLocator('Resources/config');

        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);

        $configLoader = new DelegatingLoader($resolver);

        $confDir = rtrim($this->getPath(), '/').'/Resources/config';

        $configLoader->load($confDir.'/{packages}/*.yaml', 'glob');
    }

    public function install(InstallContext $installContext): void
    {
        $this->getPluginInstaller()->install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->getPluginInstaller()->uninstall($uninstallContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->getPluginInstaller()->activate($activateContext);
        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->getPluginInstaller()->deactivate($deactivateContext);
        parent::deactivate($deactivateContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->getPluginInstaller()->update($updateContext);
        parent::update($updateContext);
    }

    private function getPluginInstaller(): PluginInstaller
    {
        return new PluginInstaller(
            $this->getPaymentMethodInstaller(),
            $this->getDatabaseInstaller(),
            $this->getCustomFieldInstaller()
        );
    }

    private function getDatabaseInstaller(): DatabaseInstaller
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        return new DatabaseInstaller(
            $connection
        );
    }

    private function getPaymentMethodInstaller(): PaymentMethodInstaller
    {
        /** @var EntityRepository<PaymentMethodCollection> $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');
        /** @var EntityRepository<SalesChannelCollection> $salesChannelRepository */
        $salesChannelRepository = $this->container->get('sales_channel.repository');
        /** @var EntityRepository<PaymentMethodCollection> $paymentMethodSalesChannelRepository */
        $paymentMethodSalesChannelRepository = $this->container->get('sales_channel_payment_method.repository');
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);

        return new PaymentMethodInstaller(
            $paymentMethodRepository,
            $salesChannelRepository,
            $paymentMethodSalesChannelRepository,
            $pluginIdProvider,
            $this->getMediaInstaller()
        );
    }

    private function getCustomFieldInstaller(): CustomFieldInstaller
    {
        /** @var EntityRepository<CustomFieldSetCollection> $customFieldSetRepository */
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        return new CustomFieldInstaller($customFieldSetRepository);
    }

    private function getMediaInstaller(): MediaInstaller
    {
        /** @var EntityRepository<MediaCollection> $mediaRepository */
        $mediaRepository = $this->container->get('media.repository');
        /** @var EntityRepository<MediaFolderCollection> $mediaFolderRepository */
        $mediaFolderRepository = $this->container->get('media_folder.repository');
        /** @var EntityRepository<PaymentMethodCollection> $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');
        /** @var FileSaver $fileSaver */
        $fileSaver = $this->container->get('Shopware\Core\Content\Media\File\FileSaver');

        return new MediaInstaller(
            $mediaRepository,
            $mediaFolderRepository,
            $paymentMethodRepository,
            $fileSaver
        );
    }
}
