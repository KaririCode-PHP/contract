<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure\Behavioral;

/**
 * Interface Countable.
 *
 * Defines the contract for counting elements.
 *
 * @category  DataStructure\Behavioral
 *
 * @see       https://kariricode.org/
 */
interface Countable
{
    /**
     * Counts the elements.
     *
     * @return int The number of elements
     */
    public function size(): int;
}
