<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient;

interface AppliesArrayDataInterface
{
    /** @param array<mixed> $data */
    public function applyData(array $data): void;
}
