<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience;

use InvalidArgumentException;
use KaririCode\Contract\Resilience\RetryPolicy;
use KaririCode\Contract\Tests\Unit\Resilience\Fixture\CallRejected;
use KaririCode\Contract\Tests\Unit\Resilience\Fixture\FullJitterBackoff;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;
use Throwable;

final class RetryPolicyTest extends TestCase
{
    #[Test]
    public function attemptCountsTheCallsAlreadyMade(): void
    {
        $policy = self::policy(maxAttempts: 3);
        $error = new RuntimeException('transient');

        self::assertTrue($policy->shouldRetry($error, 1), 'after the first failure');
        self::assertTrue($policy->shouldRetry($error, 2));
        self::assertFalse($policy->shouldRetry($error, 3), 'three calls made, none left');
    }

    #[Test]
    public function thePolicyClassifiesTheError(): void
    {
        self::assertFalse(self::policy(maxAttempts: 5)->shouldRetry(new InvalidArgumentException('bad input'), 1));
    }

    #[Test]
    public function delaysStayInsideTheFullJitterEnvelope(): void
    {
        $policy = self::policy(maxAttempts: 10, base: 100, cap: 2_000);
        $error = new RuntimeException('transient');

        foreach ([1 => 100, 2 => 200, 3 => 400, 6 => 2_000] as $attempt => $ceiling) {
            $delays = [];

            for ($sample = 0; $sample < 200; ++$sample) {
                $delay = $policy->delayMilliseconds($error, $attempt);
                self::assertGreaterThanOrEqual(0, $delay);
                self::assertLessThanOrEqual($ceiling, $delay);
                $delays[] = $delay;
            }

            self::assertGreaterThan(1, count(array_unique($delays)), 'full jitter spreads the delays');
        }
    }

    #[Test]
    public function theDelayHonoursTheWaitAnErrorCarries(): void
    {
        $delay = self::policy(maxAttempts: 3, base: 100, cap: 5_000)->delayMilliseconds(new CallRejected(2), 1);

        self::assertGreaterThanOrEqual(2_000, $delay);
        self::assertLessThanOrEqual(5_000, $delay);
    }

    #[Test]
    public function theCallerRetriesAndWaitsAsThePolicySays(): void
    {
        $failures = 2;
        $waits = [];
        $operation = static function () use (&$failures): string {
            if ($failures-- > 0) {
                throw new RuntimeException('transient');
            }

            return 'done';
        };

        $result = self::retry($operation, self::policy(maxAttempts: 3), static function (int $milliseconds) use (&$waits): void {
            $waits[] = $milliseconds;
        });

        self::assertSame('done', $result);
        self::assertCount(2, $waits);
    }

    /**
     * The retry loop of a decorator, with the wait injected instead of usleep().
     *
     * @param callable(): mixed $operation
     * @param callable(int): void $wait
     */
    private static function retry(callable $operation, RetryPolicy $policy, callable $wait): mixed
    {
        for ($attempt = 1; ; ++$attempt) {
            try {
                return $operation();
            } catch (Throwable $error) {
                if (!$policy->shouldRetry($error, $attempt)) {
                    throw $error;
                }

                $wait($policy->delayMilliseconds($error, $attempt));
            }
        }
    }

    private static function policy(int $maxAttempts, int $base = 100, int $cap = 2_000): FullJitterBackoff
    {
        return new FullJitterBackoff($maxAttempts, $base, $cap, new Randomizer(new Mt19937(2015)));
    }
}
