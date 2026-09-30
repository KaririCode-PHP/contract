<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging;

/**
 * Interface Rotator.
 *
 * Defines the contract for log rotators.
 * Log rotators are responsible for rotating log files, ensuring that logs do not
 * grow indefinitely and are managed efficiently.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface LogRotator
{
    public function shouldRotate(string $filePath): bool;

    /**
     * Rotates a log file.
     *
     * This method is responsible for rotating the specified log file, such as renaming
     * it and creating a new file for future log entries.
     *
     * @param string $filename the name of the log file to be rotated
     */
    public function rotate(string $filename): void;
}
