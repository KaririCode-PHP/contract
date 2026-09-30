<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure\Behavioral;

/**
 * Interface Searchable.
 *
 * Defines the contract for searching elements in a collection.
 *
 * @category  DataStructure\Behavioral
 *
 * @see       https://kariricode.org/
 */
interface Searchable
{
    /**
     * Checks if the collection contains a specific element.
     *
     * @param mixed $element The element to check for
     *
     * @return bool True if the element is present, false otherwise
     */
    public function contains(mixed $element): bool;

    /**
     * Finds the index of a specific element in the collection.
     *
     * @param mixed $element The element to find
     *
     * @return mixed The index of the element if found, null otherwise
     */
    public function find(mixed $element): mixed;
}
