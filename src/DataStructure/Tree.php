<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure;

use KaririCode\Contract\DataStructure\Behavioral\Modifiable;
use KaririCode\Contract\DataStructure\Behavioral\Searchable;

/**
 * Interface Tree.
 *
 * Defines the contract for tree data structures.
 *
 * @category  DataStructure
 *
 * @see       https://kariricode.org/
 */
interface Tree extends Modifiable, Searchable
{
    /**
     * Inserts a value into the tree at the specified position.
     *
     * @param int $index the index at which to insert the value
     * @param mixed $value the value to insert
     */
    public function insert(int $index, mixed $value): void;
}
