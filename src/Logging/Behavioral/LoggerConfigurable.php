<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging\Behavioral;

/**
 * LoggerConfigurable interface.
 *
 * This interface defines methods for managing configuration.
 * Configuration settings can affect the behavior of logging.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface LoggerConfigurable
{
    /**
     * Configures the logger.
     */
    public function configure(array $config): void;
}
