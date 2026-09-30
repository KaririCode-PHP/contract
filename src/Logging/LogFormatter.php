<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging;

use KaririCode\Contract\ImmutableValue;

/**
 * Interface Formatter.
 *
 * Defines the contract for log formatters.
 * Log formatters are responsible for formatting log records into a specific string format.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface LogFormatter
{
    /**
     * Formats a log record.
     *
     * This method is responsible for formatting the log record into a specific string format.
     *
     * @param ImmutableValue $record the log record to be formatted
     *
     * @return string the formatted log record as a string
     */
    public function format(ImmutableValue $record): string;

    /**
     * Formats a set of log records.
     *
     * @param ImmutableValue[] $records
     *
     * @return string The formatted log records
     */
    public function formatBatch(array $records): string;
}
