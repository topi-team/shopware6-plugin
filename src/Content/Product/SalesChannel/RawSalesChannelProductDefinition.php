<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Content\Product\SalesChannel;

use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RawSalesChannelProductDefinition extends SalesChannelProductDefinition
{
    public const SKIP_DEFAULT_AVAILABLE_FILTER = 'skip-default-available-filter';

    /**
     * copy of the original function without adding a ProductAvailableFilter when state is set.
     */
    public function processCriteria(Criteria $criteria, SalesChannelContext $context): void
    {
        if (!$criteria->hasState(self::SKIP_DEFAULT_AVAILABLE_FILTER) && !$this->hasAvailableFilter($criteria)) {
            $criteria->addFilter(
                new ProductAvailableFilter($context->getSalesChannel()->getId(),
                    ProductVisibilityDefinition::VISIBILITY_LINK)
            );
        }

        // In Shopware < 6.6.5.0, getNestingLevel() and ROOT_NESTING_LEVEL do not exist.
        // Only apply the root-level optimization when the API is available.
        if (method_exists($criteria, 'getNestingLevel')
            && defined(Criteria::class . '::ROOT_NESTING_LEVEL')
            && Criteria::ROOT_NESTING_LEVEL !== $criteria->getNestingLevel()
        ) {
            return;
        }

        if (empty($criteria->getFields())) {
            $criteria
                ->addAssociation('prices')
                ->addAssociation('unit')
                ->addAssociation('deliveryTime')
                ->addAssociation('cover.media')
            ;
        }

        if ($criteria->hasAssociation('productReviews')) {
            $association = $criteria->getAssociation('productReviews');
            $activeReviewsFilter = new MultiFilter(MultiFilter::CONNECTION_OR, [new EqualsFilter('status', true)]);
            if ($customer = $context->getCustomer()) {
                $activeReviewsFilter->addQuery(new EqualsFilter('customerId', $customer->getId()));
            }

            $association->addFilter($activeReviewsFilter);
        }
    }

    private function hasAvailableFilter(Criteria $criteria): bool
    {
        foreach ($criteria->getFilters() as $filter) {
            if ($filter instanceof ProductAvailableFilter) {
                return true;
            }
        }

        return false;
    }
}
