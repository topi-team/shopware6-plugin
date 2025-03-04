<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\OAuth\GrantType;

use BenjaminFavre\OAuthHttpClient\GrantType\GrantTypeInterface;
use BenjaminFavre\OAuthHttpClient\GrantType\Tokens;
use BenjaminFavre\OAuthHttpClient\GrantType\TokensExtractor;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Implementation of the OAuth client credentials grant type.
 */
readonly class ScopedClientCredentialsGrantType implements GrantTypeInterface
{
    use TokensExtractor;

    /**
     * @param HttpClientInterface $client       a HTTP client to be used to communicate with the OAuth server
     * @param string              $tokenUrl     the full URL of the token endpoint of the OAuth server
     * @param string              $clientId     the OAuth client ID
     * @param string              $clientSecret the OAuth client secret
     * @param string              $scope        Scope(s) to request from remote
     */
    public function __construct(
        private HttpClientInterface $client,
        private string $tokenUrl,
        private string $clientId,
        private string $clientSecret,
        private string $scope,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function getTokens(): Tokens
    {
        $response = $this->client->request('POST', $this->tokenUrl, [
            'body' => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => $this->scope,
            ]),
        ]);

        return $this->extractTokens($response);
    }
}
