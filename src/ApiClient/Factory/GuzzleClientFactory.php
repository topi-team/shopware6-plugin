<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\Factory;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use kamermans\OAuth2\OAuth2Middleware;
use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use TopiPaymentIntegration\ApiClient\OAuth\GrantType\PostBodyClientCredentials;
use TopiPaymentIntegration\Config\PluginConfigService;
use TopiPaymentIntegration\Service\Plugin\FlagLoaderInterface;

class GuzzleClientFactory
{
    /** @var array<string, GuzzleClient> */
    private array $clientCache = [];

    public function __construct(
        private readonly TokenPersistenceInterface $tokenPersistence,
        private readonly PluginConfigService $config,
        private readonly FlagLoaderInterface $flagLoader,
    ) {
    }

    public function make(?string $clientId = null, ?string $clientSecret = null): GuzzleClient
    {
        $cacheKey = $clientId.':'.$clientSecret;

        if (!isset($this->clientCache[$cacheKey])) {
            $this->clientCache[$cacheKey] = $this->createClientInstance($clientId, $clientSecret);
        }

        return $this->clientCache[$cacheKey];
    }

    protected function createClientInstance(?string $clientId = null, ?string $clientSecret = null): GuzzleClient
    {
        $reauthClientOptions = [
            // URL for access_token request
            'base_url' => $this->flagLoader->get()['identityTokenUri'],
            'defaults' => [
                'headers' => [
                    'User-Agent' => 'TopiPaymentIntegration/Shopware6 1.0',
                ],
            ],
        ];

        $reauthClientOptions['base_uri'] = $this->flagLoader->get()['identityTokenUri'];
        unset($reauthClientOptions['base_url']);

        $guzzleOptions = array_merge($reauthClientOptions, $reauthClientOptions['defaults']);
        unset($reauthClientOptions['defaults']);

        // Authorization client - this is used to request OAuth access tokens
        $reauthClient = new GuzzleClient($reauthClientOptions);
        $reauthConfig = [
            'client_id' => $clientId ?: $this->config->get('clientId'),
            'client_secret' => $clientSecret ?: $this->config->get('clientSecret'),
            'scope' => implode(' ', $this->flagLoader->get()['tokenScopes']),
        ];

        $grantType = new PostBodyClientCredentials(
            $reauthClient,
            $reauthConfig
        );
        $oauth = new OAuth2Middleware($grantType);
        $oauth->setTokenPersistence($this->tokenPersistence);

        // This is the normal Guzzle client that is used in the application
        $guzzleOptions = [
            ...$guzzleOptions,
            'base_url' => $this->getApiUrl(),
            'defaults' => [
                'auth' => 'oauth',
                'headers' => [
                    'User-Agent' => 'TopiPaymentIntegration/Shopware6 1.0',
                ],
            ],
        ];

        $handlerStack = HandlerStack::create();
        $handlerStack->push($oauth);
        $guzzleOptions['handler'] = $handlerStack;

        $guzzleOptions['base_uri'] = $this->getApiUrl();
        unset($guzzleOptions['base_url']);

        $guzzleOptions = array_merge($guzzleOptions, $guzzleOptions['defaults']);
        unset($guzzleOptions['defaults']);

        return new GuzzleClient($guzzleOptions);
    }

    protected function getApiUrl(): string
    {
        if ($this->config->get('enableLive')) {
            return $this->flagLoader->get()['apiBaseUrl']['production'];
        }

        return $this->flagLoader->get()['apiBaseUrl']['sandbox'];
    }
}
