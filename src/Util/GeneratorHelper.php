<?php

declare(strict_types=1);

namespace TopiPaymentIntegration\Util;

class GeneratorHelper
{
    /**
     * Splits the items from a Generator into chunks of the specified size.
     *
     * @template TGeneratorItem
     * @param \Generator<TGeneratorItem> $generator The input generator providing items to be processed in chunks.
     * @param int $n The size of each chunk.
     *
     * @return \Generator<array<TGeneratorItem>> A generator yielding arrays, each containing up to $n items from the input generator.
     */
    public static function chunkGenerator(\Generator $generator, int $n): \Generator
    {
        $currentChunk = [];
        foreach($generator as $currentItem) {
            $currentChunk[] = $currentItem;

            if (count($currentChunk) >= $n) {
                yield $currentChunk;
                $currentChunk = [];
            }
        }

        if (count($currentChunk) > 0) {
            yield $currentChunk;
        }
    }
}
