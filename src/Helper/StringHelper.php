<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Helper;

class StringHelper
{
    private string $originalString;

    public function __construct(string $originalString)
    {
        $this->originalString = $originalString;
    }

    public function __toString(): string
    {
        return $this->originalString;
    }

    public function toLowerSnake(): StringHelper
    {
        return new self(
            strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->originalString))
        );
    }
}
