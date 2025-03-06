<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Action;

use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use TopiPaymentIntegration\ApiClient\Client;
use TopiPaymentIntegration\ApiClient\Factory\EnvironmentFactory;
use TopiPaymentIntegration\ApiClient\ShippingMethod\ShippingMethod;

readonly class CreateShippingMethodsAction
{
    /**
     * @param EntityRepository<ShippingMethodCollection> $shippingMethodsRepository
     */
    public function __construct(
        private EntityRepository $shippingMethodsRepository,
        private Client $apiClient,
        private EnvironmentFactory $environmentFactory,
    ) {
    }

    public function execute(Context $context): void
    {
        /** @var ShippingMethodEntity $shopwareShippingMethod */
        foreach ($this->shippingMethodsRepository->search(new Criteria(), $context) as $shopwareShippingMethod) {
            $shippingMethod = new ShippingMethod();
            $shippingMethod->name = $shopwareShippingMethod->getName();
            $shippingMethod->sellerShippingMethodReference = $shopwareShippingMethod->getId();

            $this->apiClient->shippingMethod(
                $this->environmentFactory->makeEnvironment()
            )->create($shippingMethod);
        }
    }
}
