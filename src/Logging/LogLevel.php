<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging;

/**
 * Interface LogLevel.
 *
 * Defines the contract for log levels.
 * Log levels indicate the severity or importance of a log record.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface LogLevel
{
    /**
     * Gets the log level as a string.
     *
     * @return string the log level
     */
    public function getLevel(): string;

    /**
     * Gets the color level as a string.
     *
     * @return string the color level
     */
    public function getColor(): string;

    /**
     * Gets the log level as a int level.
     *
     * @return int the log level
     */
    public function getValue(): int;
}
