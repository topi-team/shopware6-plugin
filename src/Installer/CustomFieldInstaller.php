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
    public const CUSTOM_FIELD_SET_ID = '01958460a77371a7924ef42aa3182bf3'

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
        $this->customFieldSetRepository->upsert([
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
                'relations' => [[
                    'id' => '0195847602bf72c6a84a2c5466143a85',
                    'entityName' => 'order',
                ]],
            ],
        ], $context);
    }
}
