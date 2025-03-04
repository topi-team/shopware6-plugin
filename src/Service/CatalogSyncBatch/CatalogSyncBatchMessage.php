<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\CatalogSyncBatch;

class CatalogSyncBatchMessage
{
    public function __construct(
        public string $catalogSyncBatchId,
    ) {
    }
}
