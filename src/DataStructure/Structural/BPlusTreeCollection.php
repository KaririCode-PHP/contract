<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure\Structural;

use KaririCode\Contract\DataStructure\Behavioral\Sortable;
use KaririCode\Contract\DataStructure\Tree;

/**
 * Interface BPlusTreeCollection.
 *
 * Defines the contract for B+ Tree data structures.
 *
 * @category  DataStructure\Structural
 *
 * @see       https://kariricode.org/
 */
interface BPlusTreeCollection extends Collection, Tree, Sortable
{
    /**
     * Retrieves the order (maximum number of children) of the B+ Tree.
     *
     * @return int The order of the B+ Tree
     */
    public function getOrder(): int;

    /**
     * Performs a range search in the B+ Tree.
     *
     * @param mixed $start The start of the range (inclusive)
     * @param mixed $end The end of the range (inclusive)
     *
     * @return array An array of values within the specified range
     */
    public function rangeSearch(mixed $start, mixed $end): array;

    /**
     * Retrieves the minimum value in the B+ Tree.
     *
     * @return mixed The minimum value
     */
    public function getMinimum(): mixed;

    /**
     * Retrieves the maximum value in the B+ Tree.
     *
     * @return mixed The maximum value
     */
    public function getMaximum(): mixed;

    /**
     * Balances the B+ Tree to maintain its properties.
     */
    public function balance(): void;
}
