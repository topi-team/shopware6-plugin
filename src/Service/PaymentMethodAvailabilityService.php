<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use TopiPaymentIntegration\PaymentHandler\TopiAsyncPaymentHandler;
use TopiPaymentIntegration\TopiPaymentIntegrationPlugin;

readonly class PaymentMethodAvailabilityService
{
    /**
     * @param SalesChannelRepository<PaymentMethodCollection> $salesChannelPaymentMethodRepository
     */
    public function __construct(
        private SalesChannelRepository $salesChannelPaymentMethodRepository,
        private PluginIdProvider $pluginIdProvider,
    ) {
    }

    public function getTopiPaymentMethodIfAvailable(SalesChannelContext $salesChannelContext, Context $context): ?PaymentMethodEntity
    {
        return $this->getAvailablePluginPaymentMethods($salesChannelContext, $context)->first();
    }

    public function getTopiPaymentMethod(SalesChannelContext $salesChannelContext, Context $context): ?PaymentMethodEntity
    {
        return $this->getPluginPaymentMethods($salesChannelContext, $context)->first();
    }

    private function getPluginPaymentMethods(SalesChannelContext $salesChannelContext, Context $context): PaymentMethodCollection
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(TopiPaymentIntegrationPlugin::class, $context);
        $criteria = (new Criteria())
            ->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('pluginId', $pluginId),
                new EqualsFilter('handlerIdentifier', TopiAsyncPaymentHandler::class),
                new EqualsFilter('active', true),
            ]))
            ->addSorting(new FieldSorting('position'));

        $result = $this->salesChannelPaymentMethodRepository->search($criteria, $salesChannelContext);

        return $result->getEntities();
    }

    private function getAvailablePluginPaymentMethods(SalesChannelContext $salesChannelContext, Context $context): PaymentMethodCollection
    {
        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $this->getPluginPaymentMethods($salesChannelContext, $context);
        $paymentMethods->sortPaymentMethodsByPreference($salesChannelContext);

        $activeRuleIds = $salesChannelContext->getRuleIds();

        // Manually filter by availability rule: if a payment method has an availabilityRuleId
        // and it is not active in the current SalesChannelContext, drop it.
        return $paymentMethods->filter(
            function (PaymentMethodEntity $method) use ($activeRuleIds): bool {
                $availabilityRuleId = $method->getAvailabilityRuleId();

                if ($availabilityRuleId === null) {
                    return true;
                }

                return \in_array($availabilityRuleId, $activeRuleIds, true);
            }
        );
    }
}
