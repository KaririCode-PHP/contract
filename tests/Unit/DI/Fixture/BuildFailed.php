<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\DI\Fixture;

use KaririCode\Contract\Exception\ContainerFailure;
use Throwable;

/** Fixture: a bound service whose factory threw. Example: `throw BuildFailed::for('clock', $error);` */
final class BuildFailed extends \LogicException implements ContainerFailure
{
    public static function for(string $id, Throwable $previous): self
    {
        return new self(sprintf('Service "%s" could not be built.', $id), 0, $previous);
    }
}
