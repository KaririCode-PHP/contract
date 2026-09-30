<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure\Structural;

/**
 * Interface Serializable.
 *
 * Defines the contract for serializing and deserializing data.
 *
 * @category  Interfaces
 *
 * @see       https://kariricode.org/
 */
interface Serializable
{
    /**
     * Serializes the object.
     *
     * @return mixed The serialized representation of the object
     */
    public function serialize(): mixed;

    /**
     * Unserializes the object.
     *
     * @param string $data The serialized representation of the object
     */
    public function deserialize(mixed $data): void;
}
