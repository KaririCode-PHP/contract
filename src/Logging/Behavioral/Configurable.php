<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging\Behavioral;

use KaririCode\Contract\ImmutableValue;

/**
 * Configurable interface.
 *
 * This interface defines methods for managing configuration.
 * Configuration settings can affect the behavior of logging.
 *
 * Obs.:
 * ```
 * class LoggerConfig implements ImmutableValue {
 *     // Implementation details
 * }
 * ```
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface Configurable
{
    /**
     * Retrieves the current configuration.
     *
     * @return ImmutableValue the current configuration object
     */
    public function getConfig(): ImmutableValue;

    /**
     * Sets a new configuration.
     *
     * @param ImmutableValue $config the new configuration object
     *
     * @return Configurable the current instance for method chaining
     */
    public function setConfig(ImmutableValue $config): Configurable;
}
