<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience\Fixture;

use KaririCode\Contract\Resilience\CallNotPermittedException;
use RuntimeException;

/**
 * Minimal rejection: a plain readonly property satisfies the interface's
 * `{ get; }` requirement, no hook involved (E-013).
 *
 * Example: `throw new CallRejected(retryAfterSeconds: 30);`
 */
final class CallRejected extends RuntimeException implements CallNotPermittedException
{
    public function __construct(public readonly ?int $retryAfterSeconds)
    {
        parent::__construct('The call was not permitted.');
    }
}
