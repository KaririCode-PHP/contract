<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging\Behavioral;

use KaririCode\Contract\Logging\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * Loggable interface.
 *
 * This interface defines the logging methods for various log levels.
 * Implementers can log messages at different severity levels.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface Loggable
{
    /**
     * Logs with an arbitrary level.
     *
     * @param LogLevel $level the log level
     * @param string|\Stringable $message the log message
     * @param array $context additional context for the log message
     *
     */
    public function log(LogLevel $level, string|\Stringable $message, array $context = []): void;

    /**
     * Logs a debug message.
     *
     * @param string|\Stringable $message the log message
     * @param array $context additional context for the log message
     */
    public function debug(string|\Stringable $message, array $context = []): void;

    /**
     * Logs an info message.
     *
     * @param string|\Stringable $message the log message
     * @param array $context additional context for the log message
     */
    public function info(string|\Stringable $message, array $context = []): void;

    /**
     * Logs a notice message.
     *
     * @param string|\Stringable $message the log message
     * @param array $context additional context for the log message
     */
    public function notice(string|\Stringable $message, array $context = []): void;

    /**
     * Logs a warning message.
     *
     * @param string|\Stringable $message the log message
     * @param array $context additional context for the log message
     */
    public function warning(string|\Stringable $message, array $context = []): void;

    /**
     * Logs an error message.
     *
     * @param string|\Stringable $message the log message
     * @param array $context additional context for the log message
     */
    public function error(string|\Stringable $message, array $context = []): void;

    /**
     * Logs a critical message.
     *
     * @param string|\Stringable $message the log message
     * @param array $context additional context for the log message
     */
    public function critical(string|\Stringable $message, array $context = []): void;

    /**
     * Logs an alert message.
     *
     * @param string|\Stringable $message the log message
     * @param array $context additional context for the log message
     */
    public function alert(string|\Stringable $message, array $context = []): void;

    /**
     * Logs an emergency message.
     *
     * @param string|\Stringable $message the log message
     * @param array $context additional context for the log message
     */
    public function emergency(string|\Stringable $message, array $context = []): void;
}
