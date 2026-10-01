<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\DI\Fixture;

use KaririCode\Contract\Exception\NotFound;

/** Fixture: an identifier that was never bound. Example: `throw ServiceNotFound::for('clock');` */
final class ServiceNotFound extends \OutOfBoundsException implements NotFound
{
    public static function for(string $id): self
    {
        return new self(sprintf('No service is bound to "%s".', $id));
    }
}
