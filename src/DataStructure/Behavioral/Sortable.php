<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure\Behavioral;

/**
 * Interface Sortable.
 *
 * Defines the contract for classes that can be sorted.
 * Classes implementing this interface should provide an implementation
 * for the sort method to allow sorting of their elements.
 *
 * @category  DataStructure\Behavioral
 *
 * @see       https://kariricode.org/
 */
interface Sortable
{
    /**
     * Sorts the elements of the class.
     */
    public function sort(): void;
}
