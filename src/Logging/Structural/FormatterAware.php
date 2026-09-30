<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging\Structural;

use KaririCode\Contract\ImmutableValue;

/**
 * FormatterAware interface.
 *
 * This interface defines methods for managing formatters.
 * Formatters are responsible for formatting log records into strings.
 *
 * Obs.:
 * ```
 * class MyFormatter implements ImmutableValue {
 *     // Implementation details
 * }
 * ```
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface FormatterAware
{
    /**
     * Sets the formatter for the current instance.
     *
     * @param ImmutableValue $formatter the formatter to set
     *
     * @return FormatterAware returns the current instance to allow method chaining
     */
    public function setFormatter(ImmutableValue $formatter): FormatterAware;

    /**
     * Retrieves the current formatter.
     *
     * @return ImmutableValue the current formatter
     */
    public function getFormatter(): ImmutableValue;
}
