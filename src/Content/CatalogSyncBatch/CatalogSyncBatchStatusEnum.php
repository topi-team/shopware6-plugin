<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\CatalogSyncBatch;

enum CatalogSyncBatchStatusEnum: string
{
    case NEW = 'NEW';
    case COMPLETED = 'COMPLETED';
    case ERROR = 'ERROR';

    /**
     * @return array<value-of<self>,0>
     */
    public static function getCounts(): array
    {
        $counts = [];
        foreach (self::cases() as $case) {
            $counts[$case->value] = 0;
        }

        return $counts;
    }
}
