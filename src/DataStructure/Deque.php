<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure;

/**
 * Interface Deque.
 *
 * Extends the Queue interface to define the contract for double-ended queue data structures,
 * which allow elements to be added and removed from both ends.
 *
 * @category  DataStructure
 *
 * @see       https://kariricode.org/
 */
interface Deque extends Queue
{
    /**
     * Adds an element to the front of the deque.
     *
     * @param mixed $element the element to add
     */
    public function addFirst(mixed $element): void;

    /**
     * Removes and returns the element at the end of the deque.
     *
     * @return mixed the removed element or null if the deque is empty
     */
    public function removeLast(): mixed;

    /**
     * Returns the element at the end of the deque without removing it.
     *
     * @return mixed the last element or null if the deque is empty
     */
    public function peekLast(): mixed;
}
