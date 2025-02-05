<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient;

trait PreProcessOptionsTrait
{
    /**
     * @param array<mixed> $options
     *
     * @return array<mixed>
     */
    protected function preProcessOptions(array $options): array
    {
        if (isset($options['lang'])) {
            if (isset($options['json'])) {
                if (!is_array($options['json']) && $options['json'] instanceof \JsonSerializable) {
                    $options['json'] = $options['json']->jsonSerialize();
                }
                if (is_array($options['json'])) {
                    $options['json']['Accept-Language'] = $options['lang'];
                }
            }
            $options['headers'] = [
                'Accept-Language' => $options['lang'],
            ];

            unset($options['lang']);
        }

        return $options;
    }
}
