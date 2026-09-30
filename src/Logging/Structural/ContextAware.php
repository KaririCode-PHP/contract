<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging\Structural;

/**
 * ContextAware interface.
 *
 * This interface defines methods for managing context.
 * Context is additional information that can be included in log records.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface ContextAware
{
    /**
     * Sets the context for the current instance.
     *
     * @param array $context the context data to set
     *
     * @return self returns the current instance to allow method chaining
     */
    public function withContext(array $context): self;

    /**
     * Retrieves the current context.
     *
     * @return array the current context data
     */
    public function getContext(): array;
}
