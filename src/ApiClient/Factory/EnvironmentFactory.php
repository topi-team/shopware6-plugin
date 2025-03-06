<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Factory;

use TopiPaymentIntegration\ApiClient\Environment;
use TopiPaymentIntegration\Config\PluginConfigService;
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
     *  @see flags.json
     *  @see Resources/config/config.xml
     */
    public function makeEnvironment(?string $salesChannelId = null): Environment
    {
        $cacheKey = $salesChannelId ?? 'default';
        if (!isset($this->environmentCache[$cacheKey])) {
            $this->environmentCache[$salesChannelId] = $this->getEnvironmentForSalesChannel($salesChannelId);
        }

        return $this->environmentCache[$cacheKey];
    }

    private function getEnvironmentForSalesChannel(?string $salesChannelId): Environment
    {
        $environment = $this->config->getString('environment', $salesChannelId);

        return new Environment(
            $this->config->getString('clientId', $salesChannelId),
            $this->config->getString('clientSecret', $salesChannelId),
            $environment
                ? $this->flagLoader->get()['environments'][$environment]
                : $this->flagLoader->get()['environments']['sandbox']
        );
    }
}
