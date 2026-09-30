<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging\Structural;

use KaririCode\Contract\Logging\LogProcessor;

/**
 * ProcessorAware interface.
 *
 * This interface defines methods for managing processors.
 * Processors can modify or augment log records.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface ProcessorAware
{
    /**
     * Adds a log processor to the logger.
     *
     * @param LogProcessor $processor the log processor instance
     *
     * @return ProcessorAware returns the current instance to allow method chaining
     */
    public function addProcessor(LogProcessor $processor): ProcessorAware;

    /**
     * Retrieves all log processors.
     *
     * @return LogProcessor[] an array of log processor instances
     */
    public function getProcessors(): array;
}
