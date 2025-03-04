<?php

declare(strict_types=1);

namespace TopiPaymentIntegration;

class CatalogSyncContext
{
    /**
     * @param ($useQueue is true ? null : \Closure(int): void)        $onStart
     * @param ($useQueue is true ? null : \Closure(int): void)        $onProgress
     * @param ($useQueue is true ? null : \Closure(string): void)     $onSuccess
     * @param ($useQueue is true ? null : \Closure(\Exception): void) $onFailure
     */
    public function __construct(
        public bool $useQueue = true,
        private readonly ?\Closure $onStart = null,
        private readonly ?\Closure $onProgress = null,
        private readonly ?\Closure $onSuccess = null,
        private readonly ?\Closure $onFailure = null,
    ) {
        if (!$this->useQueue) {
            return;
        }

        if ($this->onStart || $this->onProgress || $this->onSuccess || $this->onFailure) {
            throw new \RuntimeException(sprintf('Giving callbacks to %s only supported in synchronous context.', self::class));
        }
    }

    public function start(int $amount): void
    {
        $this->onStart && ($this->onStart)($amount);
    }

    public function progress(int $state): void
    {
        $this->onProgress && ($this->onProgress)($state);
    }

    public function success(string $message): void
    {
        $this->onSuccess && ($this->onSuccess)($message);
    }

    public function fail(\Exception $e): void
    {
        $this->onFailure && ($this->onFailure)($e);
    }
}
