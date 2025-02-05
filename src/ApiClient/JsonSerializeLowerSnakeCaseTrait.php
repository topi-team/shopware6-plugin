<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient;

use TopiPaymentIntegration\Helper\StringHelper;

trait JsonSerializeLowerSnakeCaseTrait
{
    /** @return array<mixed> */
    public function jsonSerialize(): array
    {
        $result = [];
        foreach (array_keys(get_class_vars(self::class)) as $propertyName) {
            $propertyValue = $this->{$propertyName};

            if (is_null($propertyValue)) {
                continue;
            }

            $stringHelper = new StringHelper($propertyName);
            $result[(string) $stringHelper->toLowerSnake()] = $propertyValue;
        }

        return $result;
    }
}
