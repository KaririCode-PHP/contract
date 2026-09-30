<?php

declare(strict_types=1);

namespace KaririCode\Contract\Sanitizer;

/**
 * Interface Sanitizer.
 *
 * Defines the contract for a sanitizer that can clean or modify input data to ensure it meets certain standards.
 *
 * @category  Sanitizer
 *
 * @see       https://kariricode.org/
 */
interface Sanitizer
{
    /**
     * Sanitizes the provided input and returns the cleaned result.
     *
     * @param mixed $input The value to be sanitized
     *
     * @return mixed The sanitized result
     */
    public function sanitize(mixed $input): mixed;
}
