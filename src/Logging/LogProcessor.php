<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging;

use KaririCode\Contract\ImmutableValue;

/**
 * Interface LogProcessor.
 *
 * Defines the contract for log processors.
 * Log processors are responsible for processing log records before they are handled.
 * This can include adding additional information, modifying existing information,
 * or filtering out certain log records.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface LogProcessor
{
    /**
     * Processes a log record.
     *
     * This method is responsible for processing the log record, performing tasks such as
     * adding additional information, modifying existing information, or filtering out
     * certain log records.
     *
     * @param array $record the log record to be processed, represented as an associative array
     *
     * @return array the processed log record
     */
    public function process(ImmutableValue $record): ImmutableValue;
}
