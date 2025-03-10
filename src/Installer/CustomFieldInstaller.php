<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Installer;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Shopware\Core\System\CustomField\CustomFieldTypes;

readonly class CustomFieldInstaller implements InstallerInterface
{
    /**
     * @param EntityRepository<CustomFieldSetCollection> $customFieldSetRepository
     */
    public function __construct(
        private EntityRepository $customFieldSetRepository,
    ) {
    }

    public function install(InstallContext $installContext): void
    {
        $this->upsertCustomFieldSet($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        // nothing to do
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
        $this->upsertCustomFieldSet($updateContext->getContext());
    }

    private function upsertCustomFieldSet(Context $context): void
    {
        $this->customFieldSetRepository->create([
            [
                'name' => 'topi_order_details',
                'config' => [
                    'label' => [
                        'en-GB' => 'topi order details',
                        'de-DE' => 'topi order details',
                        Defaults::LANGUAGE_SYSTEM => 'topi order details',
                    ],
                ],
                'customFields' => [
                    [
                        'name' => 'topi_order_id',
                        'type' => CustomFieldTypes::TEXT,
                        'config' => [
                            'label' => [
                                'en-GB' => 'topi order id',
                                'de-DE' => 'topi Order-ID',
                                Defaults::LANGUAGE_SYSTEM => 'topi order id',
                            ],
                            'customFieldPosition' => 1,
                        ],
                    ],
                ],
            ],
        ], $context);
    }
}
