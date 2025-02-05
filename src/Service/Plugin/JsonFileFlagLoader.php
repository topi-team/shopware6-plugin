<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Service\Plugin;

use TopiPaymentIntegration\Exception\FileNotFoundException;
use TopiPaymentIntegration\Exception\ReadFileErrorException;
use TopiPaymentIntegration\TopiPaymentIntegrationPlugin;

class JsonFileFlagLoader implements FlagLoaderInterface
{
    private const FLAGS_FILE_NAME = 'flags.json';

    /** @var array<string, mixed> */
    protected ?array $flags = null;

    /** @return array<string, mixed> */
    public function get(): array
    {
        if (is_null($this->flags)) {
            $this->loadFlags();
        }

        return $this->flags;
    }

    protected function loadFlags(): void
    {
        try {
            $fileName = TopiPaymentIntegrationPlugin::getPluginDir().'/'.self::FLAGS_FILE_NAME;

            if (!is_file($fileName)) {
                throw new FileNotFoundException();
            }

            $fileContents = file_get_contents($fileName);

            if (false === $fileContents) {
                throw new ReadFileErrorException();
            }

            $this->flags = json_decode(
                $fileContents,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException|FileNotFoundException|ReadFileErrorException $e) {
            $this->flags = [];
        }
    }
}
