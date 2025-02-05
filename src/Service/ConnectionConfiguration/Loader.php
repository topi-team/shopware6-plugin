<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\ConnectionConfiguration;

use Shopware\Components\Model\ModelManager;
use TopiPaymentIntegration\Models\TopiConnectionConfiguration;

class Loader
{
    private ModelManager $modelManager;

    public function __construct(
        ModelManager $modelManager,
    ) {
        $this->modelManager = $modelManager;
    }

    public function get(string $key): ?string
    {
        $connectionConfiguration = $this->queryConnectionConfiguration($key);

        if (is_null($connectionConfiguration)) {
            return null;
        }

        $value = $connectionConfiguration->getValue();

        return $value;
    }

    protected function queryConnectionConfiguration(string $key): ?TopiConnectionConfiguration
    {
        $queryResult = $this->modelManager->createQueryBuilder()
            ->select('tcc')->from(TopiConnectionConfiguration::class, 'tcc')
            ->where('tcc.key = :key')
            ->setParameter('key', $key)
            ->getQuery()->getResult();

        if (empty($queryResult)) {
            return null;
        }

        return is_array($queryResult) ? $queryResult[0] : $queryResult;
    }

    public function set(string $key, string $value): void
    {
        $connectionConfiguration = $this->queryConnectionConfiguration($key);

        if (is_null($connectionConfiguration)) {
            $connectionConfiguration = new TopiConnectionConfiguration();

            $connectionConfiguration->setKey($key);
        }

        $connectionConfiguration->setValue($value);

        $this->modelManager->persist($connectionConfiguration);
        $this->modelManager->flush($connectionConfiguration);
    }

    public function remove(string $key): void
    {
        $connectionConfiguration = $this->queryConnectionConfiguration($key);

        if (is_null($connectionConfiguration)) {
            return;
        }

        $this->modelManager->remove($connectionConfiguration);
        $this->modelManager->flush();
    }
}
