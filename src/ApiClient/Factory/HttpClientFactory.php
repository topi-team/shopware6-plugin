<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Factory;

use BenjaminFavre\OAuthHttpClient\OAuthHttpClient;
use BenjaminFavre\OAuthHttpClient\TokensCache\SymfonyTokensCacheAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use TopiPaymentIntegration\ApiClient\Environment;
use TopiPaymentIntegration\ApiClient\OAuth\GrantType\ScopedClientCredentialsGrantType;
use TopiPaymentIntegration\Config\PluginConfigService;
use TopiPaymentIntegration\Service\Plugin\FlagLoaderInterface;

class HttpClientFactory
{
    private const USER_AGENT = 'TopiPaymentIntegration/Shopware6 1.0';

    /** @var array<string, HttpClientInterface> */
    private array $clientCache = [];

    public function __construct(
        private readonly FlagLoaderInterface $flagLoader,
        private readonly CacheInterface $cache,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function make(Environment $environment): HttpClientInterface
    {
        $cacheKey = $environment->hash();

        if (!isset($this->clientCache[$cacheKey])) {
            $this->clientCache[$cacheKey] = $this->createClientInstance($environment);
        }

        return $this->clientCache[$cacheKey];
    }

    protected function createClientInstance(Environment $environment): HttpClientInterface
    {
        $oauthHttpClient = $this->httpClient->withOptions([
            'headers' => [
                'User-Agent' => self::USER_AGENT,
            ],
        ]);

        $grantType = new ScopedClientCredentialsGrantType(
            $oauthHttpClient,
            $environment->config['identityTokenUrl'],
            $environment->clientId,
            $environment->clientSecret,
            implode(' ', $this->flagLoader->get()['tokenScopes'])
        );

        $httpClient = new OAuthHttpClient($this->httpClient->withOptions([
            'base_uri' => $environment->config['baseUrl'],
            'headers' => [
                'User-Agent' => self::USER_AGENT,
            ],
        ]), $grantType);

        $httpClient->setCache(new SymfonyTokensCacheAdapter($this->cache, $environment->hash()));

        return $httpClient;
    }
}
