<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure\Behavioral;

/**
 * Interface Indexable.
 *
 * Defines the contract for indexed access to elements.
 *
 * @category  DataStructure\Behavioral
 *
 * @see       https://kariricode.org/
 */
interface Indexable
{
    /**
     * Retrieves an element at the specified index.
     *
     * @param mixed $index The index of the element to retrieve
     *
     * @return mixed The element at the specified index
     */
    public function get(mixed $index): mixed;

    /**
     * Sets an element at the specified index.
     *
     * @param int $index The index where the element should be set
     * @param mixed $element The element to set
     */
    public function set(mixed $index, mixed $element): void;
}
