<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\CatalogSyncBatch;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use TopiPaymentIntegration\Content\CatalogSyncProcess\CatalogSyncProcessDefinition;

/**
 * @@phpstan-import-type CatalogSyncBatchItemIdentifier from CatalogSyncBatchEntity
 *
 * @phpstan-type CatalogSyncBatchData array{
 *     id?: string,
 *     itemIdentifiers?: CatalogSyncBatchItemIdentifier[],
 *     catalogSyncProcessId?: string,
 *     status?: value-of<CatalogSyncBatchStatusEnum>,
 * }
 */
class CatalogSyncBatchDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'topi_catalog_sync_batch';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CatalogSyncBatchEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CatalogSyncBatchCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new JsonField('item_identifiers', 'itemIdentifiers'))->addFlags(new Required()),
            (new StringField('status', 'status'))->addFlags(new Required()),

            new FkField('catalog_sync_process_id', 'catalogSyncProcessId', CatalogSyncProcessDefinition::class),

            new ManyToOneAssociationField('catalogSyncProcess', 'catalog_sync_process_id', CatalogSyncProcessDefinition::class, 'id', false),
        ]);
    }
}
