<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure\Behavioral;

/**
 * Interface Modifiable.
 *
 * Defines the contract for modifying elements in a collection.
 *
 * @category  DataStructure\Behavioral
 *
 * @see       https://kariricode.org/
 */
interface Modifiable
{
    /**
     * Adds an element to the collection.
     *
     * @param mixed $element The element to add
     */
    public function add(mixed $element): void;

    /**
     * Removes an element from the collection.
     *
     * @param mixed $element The element to remove
     *
     * @return bool True if the element was removed, false otherwise
     */
    public function remove(mixed $element): bool;

    /**
     * Clears all elements from the collection.
     */
    public function clear(): void;
}
