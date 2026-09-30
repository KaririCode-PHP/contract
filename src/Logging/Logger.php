<?php

declare(strict_types=1);

namespace KaririCode\Contract\Logging;

use KaririCode\Contract\Logging\Behavioral\Loggable;

/**
 * Interface Logger.
 *
 * @category  Logging
 *
 * @see       https://kariricode.org/
 */
interface Logger extends Loggable
{
    public function getName(): string;
}
