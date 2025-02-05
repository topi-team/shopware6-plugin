<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\ApiClient;

use TopiPaymentIntegration\Helper\StringHelper;

trait AppliesArrayDataTrait
{
    /** @param array<mixed> $data> */
    public function applyData(array $data): void
    {
        $reflectionClass = new \ReflectionClass($this);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $dataKeyName = (string) (new StringHelper($reflectionProperty->getName()))->toLowerSnake();
            if (!isset($data[$dataKeyName])) {
                continue;
            }

            $type = $reflectionProperty->getType();
            if (!$type instanceof \ReflectionNamedType || in_array($type->getName(), ['bool', 'int', 'float', 'string', 'array'])) {
                $this->{$reflectionProperty->name} = $data[$dataKeyName];
                continue;
            }

            $typeString = $type->getName();

            if (is_subclass_of($typeString, \DateTimeInterface::class)) {
                $instance = new $typeString($data[$dataKeyName]);
            } else {
                $instance = new $typeString();
                if ($instance instanceof AppliesArrayDataInterface) {
                    $instance->applyData($data[$dataKeyName]);
                }
            }

            $this->{$reflectionProperty->name} = $instance;
        }
    }
}
