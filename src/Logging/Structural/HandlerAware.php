<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging\Structural;

use KaririCode\Contract\Logging\LogHandler;
use KaririCode\Contract\Logging\LogLevel;

/**
 * HandlerAware interface.
 *
 * This interface defines methods for managing handlers.
 * Handlers are responsible for processing log records.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface HandlerAware
{
    /**
     * Adds a log handler to the logger.
     *
     * @param LogHandler $handler the log handler instance
     * @param LogLevel|null $level the logging level for the handler (optional)
     *
     * @return self returns the instance for method chaining
     */
    public function addHandler(LogHandler $handler, ?LogLevel $level = null): HandlerAware;

    /**
     * Pushes a log handler onto the stack.
     *
     * @param LogHandler $handler the log handler instance
     *
     * @return self returns the instance for method chaining
     */
    public function pushHandler(LogHandler $handler): HandlerAware;

    /**
     * Pops a log handler from the stack.
     *
     * @return LogHandler|null the log handler instance or null if the stack is empty
     */
    public function popHandler(): ?LogHandler;

    /**
     * Retrieves all log handlers.
     *
     * @return LogHandler[] an array of log handler instances
     */
    public function getHandlers(): array;
}
