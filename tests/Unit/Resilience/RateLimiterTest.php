<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Resilience;

use KaririCode\Contract\Resilience\RateLimitDecision;
use KaririCode\Contract\Tests\Unit\Resilience\Fixture\Decision;
use KaririCode\Contract\Tests\Unit\Resilience\Fixture\FixedQuotaRateLimiter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class RateLimiterTest extends TestCase
{
    #[Test]
    public function anAllowedAttemptConsumesItsCost(): void
    {
        $decision = (new FixedQuotaRateLimiter(limit: 5, windowSeconds: 60))->attempt('stage:enrich', cost: 2);

        self::assertTrue($decision->allowed);
        self::assertSame(3, $decision->remaining);
        self::assertSame(0, $decision->retryAfter);
    }

    #[Test]
    public function aDenialConsumesNothingAndSaysWhenToRetry(): void
    {
        $limiter = new FixedQuotaRateLimiter(limit: 5, windowSeconds: 60);
        $limiter->attempt('k', cost: 3);

        $denied = $limiter->attempt('k', cost: 3);

        self::assertFalse($denied->allowed);
        self::assertSame(2, $denied->remaining);
        self::assertGreaterThanOrEqual(1, $denied->retryAfter);
        self::assertTrue($limiter->attempt('k', cost: 2)->allowed, 'the denied units were not consumed');
    }

    #[Test]
    public function peekAnswersForOneUnitWithoutConsuming(): void
    {
        $limiter = new FixedQuotaRateLimiter(limit: 1, windowSeconds: 60);

        self::assertTrue($limiter->peek('k')->allowed);
        self::assertTrue($limiter->peek('k')->allowed);
        self::assertTrue($limiter->attempt('k')->allowed);
        self::assertFalse($limiter->peek('k')->allowed);
    }

    #[Test]
    public function keysPartitionTheQuota(): void
    {
        $limiter = new FixedQuotaRateLimiter(limit: 1, windowSeconds: 60);

        self::assertTrue($limiter->attempt('stage:a')->allowed);
        self::assertFalse($limiter->attempt('stage:a')->allowed);
        self::assertTrue($limiter->attempt('stage:b')->allowed);
    }

    #[Test]
    public function resetRestoresTheFullQuota(): void
    {
        $limiter = new FixedQuotaRateLimiter(limit: 2, windowSeconds: 60);
        $limiter->attempt('k', cost: 2);

        $limiter->reset('k');

        self::assertSame(2, $limiter->peek('k')->remaining);
    }

    #[Test]
    public function aReadonlyClassSatisfiesTheDecisionWithoutHooks(): void
    {
        $contract = new ReflectionClass(RateLimitDecision::class);
        $implementation = new ReflectionClass(Decision::class);

        self::assertTrue($implementation->isReadOnly());

        foreach ($contract->getProperties() as $declared) {
            $implemented = new ReflectionProperty(Decision::class, $declared->getName());

            self::assertTrue($declared->isAbstract(), $declared->getName());
            self::assertSame(['get'], array_keys($declared->getHooks()), $declared->getName());
            self::assertTrue($implemented->isReadOnly(), $declared->getName());
            self::assertFalse($implemented->hasHooks(), $declared->getName());
        }

        self::assertCount(5, $contract->getProperties());
    }
}
