<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging;

use KaririCode\Contract\Logging\Structural\FormatterAware;
use KaririCode\Contract\Logging\Structural\HandlerAware;
use KaririCode\Contract\Logging\Structural\ProcessorAware;

/**
 * Interface Logger.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface LoggerExtendedInterface extends Logger
{
    /**
     * Adds a new handler to the logger.
     */
    public function addHandler(HandlerAware $handler): self;

    /**
     * Adds a new processor to the logger.
     */
    public function addProcessor(ProcessorAware $processor): self;

    /**
     * Sets a formatter for the logger.
     */
    public function setFormatter(FormatterAware $formatter): self;

    /**
     * Gets the name of the logger.
     */
    public function getName(): string;
}
