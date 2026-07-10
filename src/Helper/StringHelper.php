<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Helper;

class StringHelper implements \Stringable
{
    public function __construct(private readonly string $originalString)
    {
    }

    public function __toString(): string
    {
        return $this->originalString;
    }

    public function toLowerSnake(): StringHelper
    {
        return new self(
            strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $this->originalString))
        );
    }
}
