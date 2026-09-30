<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience\Fixture;

use KaririCode\Contract\Resilience\CallPermit;
use LogicException;

/**
 * Minimal permit: remembers its issuer and latches once settled.
 *
 * Example: `$permit = new PermitToken($breaker);`
 */
final class PermitToken implements CallPermit
{
    public private(set) bool $isSettled = false;

    public function __construct(public readonly object $issuer) {}

    public function settle(): void
    {
        if ($this->isSettled) {
            throw new LogicException('A permit is settled exactly once.');
        }

        $this->isSettled = true;
    }
}
