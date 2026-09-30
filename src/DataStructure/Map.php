<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure;

use KaririCode\Contract\DataStructure\Behavioral\Countable;
use KaririCode\Contract\DataStructure\Behavioral\IterableCollection;

/**
 * Interface Map.
 *
 * Defines the contract for map data structures, which store key-value pairs.
 *
 * @category  DataStructure
 *
 * @see       https://kariricode.org/
 */
interface Map extends Countable, IterableCollection
{
    /**
     * Adds a key-value pair to the map.
     *
     * @param mixed $key The key to add
     * @param mixed $value The value to add
     */
    public function put(mixed $key, mixed $value): void;

    /**
     * Retrieves a value by its key.
     *
     * @param mixed $key The key to retrieve the value for
     *
     * @return mixed The value associated with the key
     */
    public function get(mixed $key): mixed;

    /**
     * Removes a key-value pair from the map.
     *
     * @param mixed $key The key to remove
     *
     * @return bool true if the key-value pair was removed, false otherwise
     */
    public function remove(mixed $key): bool;

    /**
     * Checks if the map contains a specific key.
     *
     * @param mixed $key The key to check for
     *
     * @return bool true if the key is present, false otherwise
     */
    public function containsKey(mixed $key): bool;

    /**
     * Returns all keys in the map.
     *
     * @return array The keys in the map
     */
    public function keys(): array;

    /**
     * Returns all values in the map.
     *
     * @return array The values in the map
     */
    public function values(): array;

    /**
     * Clears all key-value pairs from the map.
     */
    public function clear(): void;
}
