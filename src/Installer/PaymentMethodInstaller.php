<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Installer;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use TopiPaymentIntegration\PaymentMethod\PaymentMethodInterface;
use TopiPaymentIntegration\PaymentMethod\TopiAsyncPaymentMethod;
use TopiPaymentIntegration\TopiPaymentIntegrationPlugin;

class PaymentMethodInstaller implements InstallerInterface
{
    /**
     * @var array<class-string<PaymentMethodInterface>, non-empty-string>
     */
    public const PAYMENT_METHOD_IDS = [
        TopiAsyncPaymentMethod::class => '52c311058c8247609642ec773e30dd7b',
    ];

    public const HANDLER_IDENTIFIER_ROOT_NAMESPACE = 'TopiPaymentIntegration';

    /**
     * @var class-string<PaymentMethodInterface>[]
     */
    public const PAYMENT_METHODS = [
        TopiAsyncPaymentMethod::class,
    ];

    /**
     * @param EntityRepository<PaymentMethodCollection> $paymentMethodRepository
     * @param EntityRepository<SalesChannelCollection>  $salesChannelRepository
     * @param EntityRepository<PaymentMethodCollection> $paymentMethodSalesChannelRepository
     */
    public function __construct(
        private EntityRepository $paymentMethodRepository,
        private EntityRepository $salesChannelRepository,
        private EntityRepository $paymentMethodSalesChannelRepository,
        private PluginIdProvider $pluginIdProvider,
    ) {
    }

    public function install(InstallContext $installContext): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->upsertPaymentMethod($paymentMethod, $installContext->getContext());

            $this->enablePaymentMethodForAllSalesChannels($paymentMethod, $installContext->getContext());
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        // Only set the payment method to inactive when uninstalling. Removing the payment method would
        // cause data consistency issues, since the payment method might have been used in several orders
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->deactivatePaymentMethod($paymentMethod, $uninstallContext->getContext());
        }
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->deactivatePaymentMethod($paymentMethod, $deactivateContext->getContext());
        }
    }

    public function activate(ActivateContext $activateContext): void
    {
        foreach ($this->getPaymentMethods() as $paymentMethod) {
            $this->activatePaymentMethod($paymentMethod, $activateContext->getContext());
        }
    }

    /**
     * @return PaymentMethodInterface[]
     */
    private function getPaymentMethods(): array
    {
        $paymentMethods = [];

        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $paymentMethods[] = new $paymentMethod();
        }

        return $paymentMethods;
    }

    private function findPaymentMethodEntity(string $id, Context $context): ?PaymentMethodEntity
    {
        /** @var PaymentMethodEntity|null $paymentMethod */
        $paymentMethod = $this->paymentMethodRepository
            ->search(new Criteria([$id]), $context)
            ->first();

        return $paymentMethod;
    }

    private function upsertPaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(TopiPaymentIntegrationPlugin::class, $context);

        // Collect some common data which will be used for both update and insert
        $data = [
            'id' => $paymentMethod->getId(),
            'handlerIdentifier' => $paymentMethod->getPaymentHandler(),
            'technicalName' => $paymentMethod->getTechnicalName(),
            'pluginId' => $pluginId,
            'afterOrderEnabled' => false,
        ];

        // Find existing payment method by ID for update / install decision
        $paymentMethodEntity = $this->findPaymentMethodEntity($paymentMethod->getId(), $context);

        // Decide whether to update an existing or install a new payment method
        if ($paymentMethodEntity instanceof PaymentMethodEntity) {
            $this->updatePaymentMethod($data, $context);
        } else {
            $this->installPaymentMethod($data, $paymentMethod, $context);
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function installPaymentMethod(array $data, PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = array_merge($data, [
            'name' => $paymentMethod->getName(),
            'description' => $paymentMethod->getDescription(),
            'position' => $paymentMethod->getPosition(),
            'translations' => $paymentMethod->getTranslations(),
        ]);

        $this->paymentMethodRepository->create([$data], $context);
    }

    /**
     * @param array<mixed> $data
     */
    private function updatePaymentMethod(array $data, Context $context): void
    {
        $this->paymentMethodRepository->update([$data], $context);
    }

    private function enablePaymentMethodForAllSalesChannels(
        PaymentMethodInterface $paymentMethod,
        Context $context,
    ): void {
        $channels = $this->salesChannelRepository->searchIds(new Criteria(), $context);

        foreach ($channels->getIds() as $channel) {
            $data = [
                'salesChannelId' => $channel,
                'paymentMethodId' => $paymentMethod->getId(),
            ];

            $this->paymentMethodSalesChannelRepository->upsert([$data], $context);
        }
    }

    private function deactivatePaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = [
            'id' => $paymentMethod->getId(),
            'active' => false,
        ];

        $paymentMethodExists = $this->paymentMethodExists($data, $context);

        if (false === $paymentMethodExists) {
            return;
        }

        $this->paymentMethodRepository->update([$data], $context);
    }

    /**
     * @param array{
     *     id: string
     * } $data
     */
    private function paymentMethodExists(array $data, Context $context): bool
    {
        if (empty($data['id'])) {
            return false;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $data['id']));

        $result = $this->paymentMethodRepository->search($criteria, $context);

        return 0 !== $result->getTotal();
    }

    private function activatePaymentMethod(PaymentMethodInterface $paymentMethod, Context $context): void
    {
        $data = [
            'id' => $paymentMethod->getId(),
            'active' => true,
        ];

        $paymentMethodExists = $this->paymentMethodExists($data, $context);

        if (false === $paymentMethodExists) {
            return;
        }

        $this->paymentMethodRepository->update([$data], $context);
    }
}
