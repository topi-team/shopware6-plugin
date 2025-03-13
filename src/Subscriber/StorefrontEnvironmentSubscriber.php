<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Subscriber;

use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TopiPaymentIntegration\ApiClient\Factory\EnvironmentFactory;
use TopiPaymentIntegration\Config\ConfigValue;
use TopiPaymentIntegration\Config\PluginConfigService;
use TopiPaymentIntegration\Content\Extension\StorefrontExtension;
use TopiPaymentIntegration\Service\PaymentMethodAvailabilityService;

readonly class StorefrontEnvironmentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            GenericPageLoadedEvent::class => 'onPageLoaded',
        ];
    }

    public function __construct(
        private EnvironmentFactory $environmentFactory,
        private PluginConfigService $config,
        private PaymentMethodAvailabilityService $paymentMethodAvailabilityService,
    ) {
    }

    public function onPageLoaded(GenericPageLoadedEvent $event): void
    {
        $topiPaymentMethod = $this->paymentMethodAvailabilityService->getTopiPaymentMethodIfAvailable($event->getSalesChannelContext(), $event->getContext());

        if (!is_null($topiPaymentMethod) && $this->config->getBool(ConfigValue::ENABLE_WIDGETS, $event->getSalesChannelContext()->getSalesChannelId())) {
            $environment = $this->environmentFactory->makeEnvironment($event->getSalesChannelContext()->getSalesChannelId());
            $event->getPage()->addExtension(StorefrontExtension::EXTENSION_NAME, new StorefrontExtension(
                $environment->config['widgetJsUrl'],
                $environment->widgetId,
                $topiPaymentMethod->getId(),
            ));
        }
    }
}
