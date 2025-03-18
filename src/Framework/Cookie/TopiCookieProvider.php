<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Framework\Cookie;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use TopiPaymentIntegration\Config\ConfigValue;
use TopiPaymentIntegration\Config\PluginConfigService;

readonly class TopiCookieProvider implements CookieProviderInterface
{
    public function __construct(
        private CookieProviderInterface $originalService,
        private RequestStack $requestStack,
        private PluginConfigService $config,
    ) {
    }

    public function getCookieGroups(): array
    {
        $cookies = $this->originalService->getCookieGroups();

        $request = $this->requestStack->getCurrentRequest();
        $salesChannelId = $request?->attributes->get('sw-sales-channel-id');

        if (!$this->config->getBool(ConfigValue::ENABLE_WIDGETS, $salesChannelId)) {
            return $cookies;
        }

        foreach ($cookies as &$cookie) {
            if (!\is_array($cookie)) {
                continue;
            }

            if (!$this->isComfortCookieGroup($cookie)) {
                continue;
            }

            if (!array_key_exists('entries', $cookie)) {
                continue;
            }

            $cookie['entries'][] = [
                'snippet_name' => 'topi.cookie.name',
                'snippet_description' => 'topi.cookie.description ',
                'cookie' => 'topi-widgets',
                'value' => '1',
                'expiration' => '30',
            ];
        }

        return $cookies;
    }

    private function isComfortCookieGroup(array $cookie): bool
    {
        return array_key_exists('snippet_name', $cookie)
            && 'cookie.groupComfortFeatures' === $cookie['snippet_name'];
    }
}
