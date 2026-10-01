<?php

declare(strict_types=1);

namespace KaririCode\Contract\Resilience;

use Throwable;

/**
 * Decides whether a failed call runs again and how long to wait before it.
 *
 * The policy only decides; the caller (a retry decorator) counts attempts
 * and waits. The error lets a policy classify failures (retry a transient
 * fault, never retry invalid input) and honour a delay the error carries,
 * such as CallNotPermittedException::$retryAfterSeconds.
 *
 * Preconditions: $attempt is the number of calls already made, all of them
 * failed — 1 after the first failure. delayMilliseconds() is asked only
 * after shouldRetry() returned true, and returns 0 or more.
 *
 * Thread-safety: implementations are stateless — everything they need
 * arrives as arguments — so one instance serves concurrent retries.
 * Randomised delays (jitter) are expected. On PHP-FPM the caller's wait
 * blocks the worker: keep attempts few and delays capped.
 *
 * Side effects: none; the policy only decides, and the caller waits and
 * calls again.
 *
 * Example:
 *
 *     for ($attempt = 1; ; ++$attempt) {
 *         try {
 *             return $operation();
 *         } catch (\Throwable $error) {
 *             if (!$policy->shouldRetry($error, $attempt)) {
 *                 throw $error;
 *             }
 *             usleep($policy->delayMilliseconds($error, $attempt) * 1_000);
 *         }
 *     }
 *
 * References: Brooker, "Exponential Backoff And Jitter", AWS Architecture
 * Blog (2015); Resilience4j, "Retry"; Nygard, Release It! (2nd ed.), ch. 5.
 *
 * @see https://aws.amazon.com/blogs/architecture/exponential-backoff-and-jitter/
 * @see https://resilience4j.readme.io/docs/retry
 */
interface RetryPolicy
{
    /** Whether the call that failed with $error should run again. */
    public function shouldRetry(Throwable $error, int $attempt): bool;

    /** Milliseconds to wait before the next call; 0 or more. */
    public function delayMilliseconds(Throwable $error, int $attempt): int;
}
