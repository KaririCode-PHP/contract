<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure;

/**
 * Interface Heap.
 *
 * Defines the contract for heap data structures.
 * A heap is a specialized tree-based data structure that satisfies the heap property.
 *
 * @category  DataStructure
 *
 * @see       https://kariricode.org/
 */
interface Heap
{
    /**
     * Adds an element to the heap.
     *
     * @param mixed $element the element to add
     */
    public function add(mixed $element): void;

    /**
     * Inserts an element into the heap at the specified position.
     *
     * @param int $index the index at which to insert the element
     * @param mixed $element the element to insert
     */
    public function insert(int $index, mixed $element): void;

    /**
     * Removes and returns the root element from the heap.
     *
     * @return mixed the removed root element
     */
    public function poll(): mixed;

    /**
     * Removes a specific element from the heap.
     *
     * @param mixed $element the element to remove
     *
     * @return bool true if the element was removed, false otherwise
     */
    public function remove(mixed $element): bool;

    /**
     * Returns the root element without removing it.
     *
     * @return mixed the root element
     */
    public function peek(): mixed;
}
