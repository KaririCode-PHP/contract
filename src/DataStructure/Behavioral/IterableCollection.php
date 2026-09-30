<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure\Behavioral;

/**
 * Interface IterableCollection.
 *
 * Provides the ability to iterate over a collection.
 *
 * @category  DataStructure\Behavioral
 *
 * @see      https://kariricode.org/
 */
interface IterableCollection extends \Traversable
{
    /**
     * Gets an iterator for the collection.
     *
     * @return \Iterator an iterator for the collection
     */
    public function getIterator(): \Iterator;
}
