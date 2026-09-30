<?php

declare(strict_types=1);

namespace KaririCode\Contract\Serializer;

/**
 * Interface Serializer.
 *
 * Defines the contract for a serializer that can convert data to and from specific formats.
 *
 * @category  Serializer
 *
 * @see       https://kariricode.org/
 */
interface Serializer
{
    /**
     * Serializes the provided input into a specific format.
     *
     * @param mixed $input The value to be serialized
     *
     * @return string The serialized data
     */
    public function serialize(mixed $input): string;

    /**
     * Deserializes the provided data from a specific format.
     *
     * @param string $data The serialized data
     *
     * @return mixed The deserialized value
     */
    public function deserialize(string $data): mixed;
}
