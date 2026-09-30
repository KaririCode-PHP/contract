<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging;

use KaririCode\Contract\ImmutableValue;

/**
 * Interface LogHandler.
 *
 * Defines the contract for log handlers.
 * Log handlers are responsible for processing log records and implementing specific
 * logging behavior, such as writing log entries to files, sending them to remote servers,
 * or displaying them on the console.
 *
 * Implementations of this interface should focus on the `handle` method, which
 * processes a single log record represented as an array.
 *
 * @category  Logging
 *
 * @see      https://kariricode.org/
 */
interface LogHandler
{
    /**
     * Processes a log record.
     *
     * This method is responsible for handling the log record, performing tasks such as
     * writing it to a file, sending it over the network, or any other logging behavior.
     *
     * @param ImmutableValue $record the log record to be processed, represented as an associative array
     */
    public function handle(ImmutableValue $record): void;

    /**
     * Checks whether the handler is handling the given log level.
     *
     * @param ImmutableValue $record the log record to be processed, represented as an associative array
     */
    public function isHandling(ImmutableValue $record): bool;
}
