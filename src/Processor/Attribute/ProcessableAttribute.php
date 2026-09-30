<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor\Attribute;

/**
 * Interface ProcessableAttribute.
 *
 * Defines the contract for an attribute that can hold a collection of processors and provide a fallback value if needed.
 *
 * @category  Processor
 *
 * @see       https://kariricode.org/
 */
interface ProcessableAttribute
{
    /**
     * Retrieves the processors associated with the attribute.
     *
     * @return array The list of processors
     */
    public function getProcessors(): array;
}
