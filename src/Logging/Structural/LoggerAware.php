<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging\Structural;

use KaririCode\Contract\Logging\Logger;

/**
 * LoggerAware interface.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface LoggerAware
{
    /**
     * Sets a logger instance on the object.
     */
    public function setLogger(Logger $logger): void;
}
