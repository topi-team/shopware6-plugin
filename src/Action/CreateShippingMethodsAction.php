<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Action;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use TopiPaymentIntegration\ApiClient\Client;
use TopiPaymentIntegration\ApiClient\Factory\EnvironmentFactory;
use TopiPaymentIntegration\ApiClient\ShippingMethod\ShippingMethod;
use TopiPaymentIntegration\Config\ConfigValue;
use TopiPaymentIntegration\Config\PluginConfigService;

readonly class CreateShippingMethodsAction
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private EntityRepository $salesChannelRepository,
        private PluginConfigService $config,
        private Client $apiClient,
        private EnvironmentFactory $environmentFactory,
    ) {
    }

    public function execute(Context $context): void
    {
        $shippingMethodsToCreate = [];

        $criteria = (new Criteria())
            ->addAssociation('shippingMethods');

        $salesChannels = $this->salesChannelRepository->search($criteria, $context);
        foreach ($salesChannels as $salesChannel) {
            if (!$this->config->getBool(ConfigValue::CATALOG_SYNC_ACTIVE_IN_SALES_CHANNEL, $salesChannel->getId())) {
                continue;
            }

            foreach ($salesChannel->getShippingMethods() as $shippingMethod) {
                // use the id as key for deduplication
                $shippingMethodsToCreate[$shippingMethod->getId()] = $shippingMethod;
            }
        }

        foreach ($shippingMethodsToCreate as $shopwareShippingMethod) {
            $shippingMethod = new ShippingMethod();
            $shippingMethod->name = $shopwareShippingMethod->getName();
            $shippingMethod->sellerShippingMethodReference = $shopwareShippingMethod->getId();

            $this->apiClient->shippingMethod(
                $this->environmentFactory->makeEnvironment()
            )->create($shippingMethod);
        }
    }
}
