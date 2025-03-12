<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Subscriber;

use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TopiPaymentIntegration\ApiClient\Factory\EnvironmentFactory;
use TopiPaymentIntegration\Content\Extension\StorefrontExtension;

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
    ) {
    }

    public function onPageLoaded(GenericPageLoadedEvent $event): void
    {
        $environment = $this->environmentFactory->makeEnvironment($event->getSalesChannelContext()->getSalesChannelId());
        $event->getPage()->addExtension(StorefrontExtension::EXTENSION_NAME, new StorefrontExtension(
            $environment->config['widgetJsUrl'],
            $environment->widgetId,
        ));
    }
}
