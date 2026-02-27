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
    public const CUSTOM_FIELD_SET_ID = '01958460a77371a7924ef42aa3182bf3';
    public const CUSTOM_FIELD_ID = '01958460a77371a7924ef42aa40f92fc';
    public const RELATION_ID = '0195847602bf72c6a84a2c5466143a85';

    public const PRODUCT_CUSTOM_FIELD_SET_ID = 'b69cf0a1ff275b3aad2cd7b149903527';
    public const PRODUCT_CUSTOM_FIELD_ID = '38a55536a5815b889a646469322d7ee0';
    public const PRODUCT_RELATION_ID = '2282c6bae9e05881a813c9c85131481b';

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
                'id' => self::CUSTOM_FIELD_SET_ID,
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
                        'id' => self::CUSTOM_FIELD_ID,
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
                    'id' => self::RELATION_ID,
                    'entityName' => 'order',
                ]],
            ],
            [
                'id' => self::PRODUCT_CUSTOM_FIELD_SET_ID,
                'name' => 'topi_product_details',
                'config' => [
                    'label' => [
                        'en-GB' => 'topi product details',
                        'de-DE' => 'topi product details',
                        Defaults::LANGUAGE_SYSTEM => 'topi product details',
                    ],
                ],
                'customFields' => [
                    [
                        'id' => self::PRODUCT_CUSTOM_FIELD_ID,
                        'name' => 'topi_is_inactive',
                        'type' => CustomFieldTypes::SWITCH,
                        'config' => [
                            'label' => [
                                'en-GB' => 'inactive (topi)',
                                'de-DE' => 'inaktiv (topi)',
                                Defaults::LANGUAGE_SYSTEM => 'inactive (topi)',
                            ],
                            'helpText' => [
                                'en-GB' => 'If enabled, this product will be marked as inactive in the topi catalog, regardless of its Shopware active status.',
                                'de-DE' => 'Wenn aktiviert, wird dieses Produkt im topi-Katalog als inaktiv markiert, unabhängig vom Shopware-Aktivstatus.',
                                Defaults::LANGUAGE_SYSTEM => 'If enabled, this product will be marked as inactive in the topi catalog, regardless of its Shopware active status.',
                            ],
                            'componentName' => 'sw-field',
                            'type' => 'switch',
                            'customFieldPosition' => 1,
                        ],
                    ],
                ],
                'relations' => [[
                    'id' => self::PRODUCT_RELATION_ID,
                    'entityName' => 'product',
                ]],
            ],
        ], $context);
    }
}
