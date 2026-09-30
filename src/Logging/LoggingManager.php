<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging;

use KaririCode\Contract\Logging\Behavioral\Configurable;
use KaririCode\Contract\Logging\Behavioral\Loggable;
use KaririCode\Contract\Logging\Structural\ContextAware;
use KaririCode\Contract\Logging\Structural\FormatterAware;
use KaririCode\Contract\Logging\Structural\HandlerAware;
use KaririCode\Contract\Logging\Structural\ProcessorAware;

/**
 * Interface LoggingManager.
 *
 * Combines various logging functionalities, including logging methods, handler management,
 * processor management, formatter management, context management, and configuration.
 * This interface serves as a comprehensive manager for all aspects of logging.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface LoggingManager extends
    Loggable,
    HandlerAware,
    ProcessorAware,
    FormatterAware,
    ContextAware,
    Configurable
{
    /**
     * Gets the name of the logger.
     *
     * @return string the name of the logger
     */
    public function getName(): string;
}
