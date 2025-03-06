<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;

/**
 * @see \Shopware\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface::processCriteria
 * @see \Shopware\Core\Content\Product\SalesChannel\SalesChannelProductDefinition::processCriteria
 */
class EmptyProductAvailableFilter extends ProductAvailableFilter
{
    public function __construct()
    {
        $this->operator = mb_strtoupper(trim(self::CONNECTION_AND));
        $this->queries = [];
    }
}
