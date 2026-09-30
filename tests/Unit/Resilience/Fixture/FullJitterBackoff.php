<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience\Fixture;

use InvalidArgumentException;
use KaririCode\Contract\Resilience\CallNotPermittedException;
use KaririCode\Contract\Resilience\RetryPolicy;
use Override;
use Random\Randomizer;
use Throwable;

/**
 * Minimal RetryPolicy: exponential backoff with full jitter (Brooker,
 * 2015), delay = random(0, min(cap, base × 2^(attempt − 1))). Never retries
 * invalid input; waits at least the delay a rejection carries.
 *
 * Example: `new FullJitterBackoff(maxAttempts: 3, baseMilliseconds: 100, capMilliseconds: 2_000, random: new Randomizer())`
 */
final readonly class FullJitterBackoff implements RetryPolicy
{
    public function __construct(
        private int $maxAttempts,
        private int $baseMilliseconds,
        private int $capMilliseconds,
        private Randomizer $random,
    ) {}

    #[Override]
    public function shouldRetry(Throwable $error, int $attempt): bool
    {
        return $attempt < $this->maxAttempts && !$error instanceof InvalidArgumentException;
    }

    #[Override]
    public function delayMilliseconds(Throwable $error, int $attempt): int
    {
        $ceiling = min($this->capMilliseconds, $this->baseMilliseconds << min($attempt - 1, 30));
        $jitter = $this->random->getInt(0, $ceiling);
        $hint = $error instanceof CallNotPermittedException ? ($error->retryAfterSeconds ?? 0) * 1_000 : 0;

        return min($this->capMilliseconds, max($jitter, $hint));
    }
}
