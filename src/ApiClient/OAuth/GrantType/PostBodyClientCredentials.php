<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient\OAuth\GrantType;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use kamermans\OAuth2\GrantType\GrantTypeInterface;
use kamermans\OAuth2\Signer\ClientCredentials\SignerInterface;
use kamermans\OAuth2\Utils\Collection;
use Psr\Http\Message\StreamInterface;

class PostBodyClientCredentials implements GrantTypeInterface
{
    /**
     * Configuration settings.
     */
    private Collection $config;

    /**
     * @param array{client_id: string, client_secret?: string} $config
     */
    public function __construct(
        private readonly ClientInterface $client,
        array $config,
    ) {
        $this->config = Collection::fromConfig(
            $config,
            // Defaults
            [
                'client_secret' => '',
                'scope' => '',
            ],
            // Required
            [
                'client_id',
            ]
        );
    }

    /**
     * @param string|null $refreshToken
     *
     * @return array{
     *     access_token: string,
     *     refresh_token?: string,
     *     expires_in?: int,
     *     expires?: int
     * }
     *
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function getRawData(SignerInterface $clientCredentialsSigner, $refreshToken = null)
    {
        $request = new Request(
            'POST',
            '', // will be expanded to the base_uri set within the client
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $this->getPostBody()
        );

        $response = $this->client->send($request);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function getPostBody(): StreamInterface
    {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ];

        if ($this->config['scope']) {
            $data['scope'] = $this->config['scope'];
        }

        if (!empty($this->config['audience'])) {
            $data['audience'] = $this->config['audience'];
        }

        return Utils::streamFor(http_build_query($data, '', '&'));
    }
}
