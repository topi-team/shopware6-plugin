<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\Product\SalesChannel;

use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RawSalesChannelProductDefinition extends SalesChannelProductDefinition
{
    public const SKIP_DEFAULT_AVAILABLE_FILTER = 'skip-default-available-filter';

    /**
     * Delegates to the core implementation, but drops the ProductAvailableFilter
     * the core adds by default when the skip state is set.
     */
    public function processCriteria(Criteria $criteria, SalesChannelContext $context): void
    {
        if (!$criteria->hasState(self::SKIP_DEFAULT_AVAILABLE_FILTER)) {
            parent::processCriteria($criteria, $context);

            return;
        }

        $originalFilters = $criteria->getFilters();

        parent::processCriteria($criteria, $context);

        $processedFilters = $criteria->getFilters();
        $criteria->resetFilters();

        foreach ($processedFilters as $filter) {
            // keep an available filter only if the caller added it explicitly
            if ($filter instanceof ProductAvailableFilter && !in_array($filter, $originalFilters, true)) {
                continue;
            }

            $criteria->addFilter($filter);
        }
    }
}
