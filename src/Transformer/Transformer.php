<?php

declare(strict_types=1);

namespace KaririCode\Contract\Transformer;

/**
 * Interface Transformer.
 *
 * Defines the contract for a transformer that can transform input data.
 *
 * @category  Transformer
 *
 * @see       https://kariricode.org/
 */
interface Transformer
{
    /**
     * Transforms the provided object using registered transformers.
     *
     * @param mixed $object The object to be transformed
     *
     * @return mixed The result of the transformation process
     */
    public function transform(mixed $object): mixed;
}
