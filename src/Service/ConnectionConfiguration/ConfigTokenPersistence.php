<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\ConnectionConfiguration;

use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use kamermans\OAuth2\Token\TokenInterface;

class ConfigTokenPersistence implements TokenPersistenceInterface
{
    private const TOKEN_KEY = 'oauth2token';

    public function __construct(private Loader $loader)
    {
    }

    public function restoreToken(TokenInterface $token)
    {
        $tokenData = $this->loader->get(self::TOKEN_KEY);

        if (is_null($tokenData)) {
            return null;
        }

        $unserializedTokenData = unserialize($tokenData, [
            'allowed_classes' => [],
        ]);

        if (!is_array($unserializedTokenData) || !method_exists($token, 'unserialize')) {
            return null;
        }

        return $token->unserialize(
            $unserializedTokenData
        );
    }

    public function saveToken(TokenInterface $token): void
    {
        $tokenData = method_exists($token, 'serialize')
            ? $token->serialize()
            : get_object_vars($token);

        $this->loader->set(self::TOKEN_KEY, serialize($tokenData));
    }

    public function deleteToken(): void
    {
        $this->loader->remove(self::TOKEN_KEY);
    }

    public function hasToken(): bool
    {
        return !is_null($this->loader->get(self::TOKEN_KEY));
    }
}
