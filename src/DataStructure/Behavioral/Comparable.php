<?php

declare(strict_types=1);

namespace KaririCode\Contract\DataStructure\Behavioral;

/**
 * Interface Comparable.
 *
 * Defines the contract for objects that can be compared.
 *
 * @category  DataStructure\Behavioral
 *
 * @see       https://kariricode.org/
 */
interface Comparable
{
    /**
     * Compares this object with the specified object for order.
     *
     * @param mixed $other The object to compare with
     *
     * @return int a negative integer, zero, or a positive integer as this object
     *             is less than, equal to, or greater than the specified object
     */
    public function compareTo(mixed $other): int;
}
