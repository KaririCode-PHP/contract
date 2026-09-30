<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure;

use KaririCode\Contract\DataStructure\Behavioral\Modifiable;
use KaririCode\Contract\DataStructure\Behavioral\Searchable;

/**
 * Interface Set.
 *
 * Defines the contract for set data structures, which do not allow duplicate elements.
 *
 * @category  DataStructure
 *
 * @see       https://kariricode.org/
 *
 * @template TValue O tipo de valor que o conjunto irá conter.
 *
 * @extends Modifiable<TValue>
 * @extends Searchable<TValue>
 * @extends \Traversable<int, TValue>
 */
interface Set extends Modifiable, Searchable, \Traversable
{
    /**
     * Realiza a união deste conjunto com outro.
     *
     * @param Set<TValue> $other o outro conjunto para a união
     *
     * @return Set<TValue> o conjunto resultante da união
     */
    public function union(Set $other): Set;

    /**
     * Realiza a interseção deste conjunto com outro.
     *
     * @param Set<TValue> $other o outro conjunto para a interseção
     *
     * @return Set<TValue> o conjunto resultante da interseção
     */
    public function intersection(Set $other): Set;

    /**
     * Realiza a diferença deste conjunto com outro.
     *
     * @param Set<TValue> $other o outro conjunto para a diferença
     *
     * @return Set<TValue> o conjunto resultante da diferença
     */
    public function difference(Set $other): Set;
}
