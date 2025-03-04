<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\CatalogSyncProcess;

enum CatalogSyncProcessStatusEnum: string
{
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case ERROR = 'ERROR';
}
