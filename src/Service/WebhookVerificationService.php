<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service;

use Svix\Exception\WebhookVerificationException;
use Svix\Webhook;
use TopiPaymentIntegration\Config\ConfigValue;
use TopiPaymentIntegration\Config\PluginConfigService;
use TopiPaymentIntegration\Exception\WebhookVerificationFailedException;

readonly class WebhookVerificationService
{
    public function __construct(
        private PluginConfigService $config,
    ) {
    }

    /**
     * @return string[]
     */
    protected function readWebhookSigningSecrets(): array
    {
        $signingSecretString = $this->config->getString(ConfigValue::WEBHOOK_SIGNING_SECRETS);

        // save some computations
        if (empty($signingSecretString)) {
            return [];
        }

        $result = [];
        foreach (explode(',', $signingSecretString) as $item) {
            $result[] = trim($item);
        }

        return $result;
    }

    /**
     * @param string[] $headers
     *
     * @return array<mixed>
     *
     * @throws \JsonException
     * @throws WebhookVerificationFailedException
     */
    public function verify(string $payload, array $headers): array
    {
        if (!$this->config->getBool(ConfigValue::ENABLE_WEBHOOK_SIGNATURE_CHECKS)) {
            return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        }

        foreach ($this->readWebhookSigningSecrets() as $signingSecret) {
            $wh = new Webhook($signingSecret);

            try {
                return $wh->verify($payload, $headers);
            } catch (WebhookVerificationException $e) {
            }
        }

        throw new WebhookVerificationFailedException();
    }
}
