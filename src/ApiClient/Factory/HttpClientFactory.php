<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Factory;

use BenjaminFavre\OAuthHttpClient\OAuthHttpClient;
use BenjaminFavre\OAuthHttpClient\TokensCache\SymfonyTokensCacheAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TopiPaymentIntegration\ApiClient\OAuth\GrantType\ScopedClientCredentialsGrantType;
use TopiPaymentIntegration\Config\PluginConfigService;
use TopiPaymentIntegration\Service\Plugin\FlagLoaderInterface;

class HttpClientFactory
{
    private const USER_AGENT = 'TopiPaymentIntegration/Shopware6 1.0';

    /** @var array<string, HttpClientInterface> */
    private array $clientCache = [];

    public function __construct(
        private readonly PluginConfigService $config,
        private readonly FlagLoaderInterface $flagLoader,
        private readonly CacheInterface $cache,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function make(?string $clientId = null, ?string $clientSecret = null): HttpClientInterface
    {
        $cacheKey = $clientId.':'.$clientSecret;

        if (!isset($this->clientCache[$cacheKey])) {
            $this->clientCache[$cacheKey] = $this->createClientInstance($clientId, $clientSecret);
        }

        return $this->clientCache[$cacheKey];
    }

    protected function createClientInstance(?string $clientId = null, ?string $clientSecret = null): HttpClientInterface
    {
        $environmentUrls = $this->getEnvironmentConfig();

        $oauthHttpClient = $this->httpClient->withOptions([
            'headers' => [
                'User-Agent' => self::USER_AGENT,
            ],
        ]);

        $grantType = new ScopedClientCredentialsGrantType(
            $oauthHttpClient,
            $environmentUrls['identityTokenUrl'],
            $clientId ?: $this->config->get('clientId'),
            $clientSecret ?: $this->config->get('clientSecret'),
            implode(' ', $this->flagLoader->get()['tokenScopes'])
        );

        $httpClient = new OAuthHttpClient($this->httpClient->withOptions([
            'base_uri' => $environmentUrls['baseUrl'],
            'headers' => [
                'User-Agent' => self::USER_AGENT,
            ],
        ]), $grantType);

        $httpClient->setCache(new SymfonyTokensCacheAdapter($this->cache, md5($clientId.':'.$clientSecret)));

        return $httpClient;
    }

    /**
     * @see flags.json
     * @see Resources/config/config.xml
     * @return array{baseUrl: string, identityTokenUrl: string}
     */
    protected function getEnvironmentConfig(): array
    {
        if ($environment = $this->config->get('environment')) {
            return $this->flagLoader->get()['environments'][$environment];
        }

        return $this->flagLoader->get()['environments']['sandbox'];
    }
}
