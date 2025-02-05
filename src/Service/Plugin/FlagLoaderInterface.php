<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\Plugin;

interface FlagLoaderInterface
{
    /** @return array<string, mixed> */
    public function get(): array;
}
