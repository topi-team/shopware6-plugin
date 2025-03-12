<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Factory;

use TopiPaymentIntegration\ApiClient\Environment;
use TopiPaymentIntegration\Config\ConfigValue;
use TopiPaymentIntegration\Config\PluginConfigService;
use TopiPaymentIntegration\Exception\InvalidEnvironmentException;
use TopiPaymentIntegration\Service\Plugin\FlagLoaderInterface;

class EnvironmentFactory
{
    /** @var array<string, Environment> */
    private array $environmentCache = [];

    public function __construct(
        private readonly FlagLoaderInterface $flagLoader,
        private readonly PluginConfigService $config,
    ) {
    }

    /**
     * When no $salesChannelId is given, this method loads the default environment from the config
     * otherwise it loads the Environment from the given sales-channel's config.
     *
     * @see flags.json
     * @see Resources/config/config.xml
     */
    public function makeEnvironment(?string $salesChannelId = null): Environment
    {
        $cacheKey = $salesChannelId ?? 'default';
        if (!isset($this->environmentCache[$cacheKey])) {
            $this->environmentCache[$cacheKey] = $this->getEnvironmentForSalesChannel($salesChannelId);
        }

        $this->validateEnvironment($this->environmentCache[$cacheKey], $salesChannelId);

        return $this->environmentCache[$cacheKey];
    }

    private function validateEnvironment(Environment $environment, ?string $salesChannelId = null): void
    {
        $catalogSyncActive = $this->config->getBool(ConfigValue::CATALOG_SYNC_ACTIVE_IN_SALES_CHANNEL, $salesChannelId);
        if ($catalogSyncActive && in_array('', [
            $environment->clientId,
            $environment->clientSecret,
        ], true)) {
            throw new InvalidEnvironmentException('Required configuration value (clientId / clientSecret) is empty!');
        }

        $widgetsActive = $this->config->getBool(ConfigValue::ENABLE_WIDGETS, $salesChannelId);
        if ($widgetsActive && '' === $environment->widgetId) {
            throw new InvalidEnvironmentException('Required configuration value widgetId is empty!');
        }
    }

    private function getEnvironmentForSalesChannel(?string $salesChannelId): Environment
    {
        $environment = $this->config->getString(ConfigValue::ENVIRONMENT, $salesChannelId);

        return new Environment(
            $this->config->getString(ConfigValue::CLIENT_ID, $salesChannelId),
            $this->config->getString(ConfigValue::CLIENT_SECRET, $salesChannelId),
            $this->config->getString(ConfigValue::WIDGET_ID, $salesChannelId),
            $environment
                ? $this->flagLoader->get()['environments'][$environment]
                : $this->flagLoader->get()['environments']['sandbox']
        );
    }
}
