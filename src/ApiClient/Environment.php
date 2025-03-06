<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient;

class Environment
{
    private string $hash;

    /**
     * @param array{baseUrl: string, identityTokenUrl: string} $config
     */
    public function __construct(
        public string $clientId,
        public string $clientSecret,
        public array $config,
    ) {
        $this->hash = md5(serialize([
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'config' => $this->config,
        ]));
    }

    public function hash(): string
    {
        return $this->hash;
    }
}
