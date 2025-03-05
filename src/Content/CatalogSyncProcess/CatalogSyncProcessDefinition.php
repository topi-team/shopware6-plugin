<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\CatalogSyncProcess;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use TopiPaymentIntegration\Content\CatalogSyncBatch\CatalogSyncBatchDefinition;

/**
 * @phpstan-import-type CatalogSyncBatchData from CatalogSyncBatchDefinition
 *
 * @phpstan-type CatalogSyncProcessData array{
 *     id?: string,
 *     startDate?: \DateTimeInterface,
 *     endDate?: \DateTimeInterface,
 *     status?: value-of<CatalogSyncProcessStatusEnum>,
 *     salesChannelId?: string,
 *     catalogSyncBatches?: array<CatalogSyncBatchData>,
 * }
 */
class CatalogSyncProcessDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'topi_catalog_sync_process';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CatalogSyncProcessEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CatalogSyncProcessCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new DateTimeField('start_date', 'startDate'))->addFlags(new Required()),
            new DateTimeField('end_date', 'endDate'),
            (new StringField('status', 'status'))->addFlags(new Required()),

            new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),

            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id', false),
            new OneToManyAssociationField('catalogSyncBatches', CatalogSyncBatchDefinition::class, 'id'),
        ]);
    }
}
