<?php

declare(strict_types=1);

namespace KaririCode\Contract\Resilience;

use Throwable;

/**
 * A circuit breaker driven by the caller: acquire a permit, run the call,
 * then report its outcome with the permit. execute() is the same protocol
 * in one step.
 *
 * For callers that cannot hand the call to execute(): decorators that put
 * another guard between admission and the call (a rate limit), and
 * asynchronous flows that finish later.
 *
 * Preconditions: settle every permit exactly once — recordSuccess() or
 * recordFailure() when the call ran, releasePermit() when it did not.
 * Pass every Throwable of the admitted call: the breaker, not the caller,
 * decides whether it counts, so errors it is configured to ignore (invalid
 * input, validation) never open the circuit. Durations are measured by the
 * implementation from the permit; callers pass none.
 *
 * Thread-safety: implementations keep circuit state where every process
 * sees it (Redis) and change it atomically; the permit carries the call's
 * identity, so one breaker serves concurrent calls. State kept in process
 * memory starts over on every PHP-FPM request.
 *
 * Side effects: acquiring may write circuit state (an admission can move
 * an open circuit to half-open and reserve a trial); recordSuccess() and
 * recordFailure() write the outcome; releasePermit() frees the trial.
 * Implementations typically dispatch one event per outcome.
 *
 * Example:
 *
 *     $permit = $breaker->tryAcquirePermit();
 *     if ($permit === null) {
 *         return $this->skip($stage);               // open, or half-open trials in use
 *     }
 *     if (!$limiter->attempt($stage)->allowed) {
 *         $breaker->releasePermit($permit);         // admitted, but the call will not run
 *         return $this->throttle($stage);
 *     }
 *     try {
 *         $output = $processor->process($input);
 *     } catch (\Throwable $error) {
 *         $breaker->recordFailure($permit, $error);
 *         throw $error;
 *     }
 *     $breaker->recordSuccess($permit);
 *
 * References: Nygard, Release It! (2nd ed.), ch. 5 "Circuit Breaker";
 * Fowler, "CircuitBreaker" (2014); Resilience4j, "CircuitBreaker".
 *
 * @see https://martinfowler.com/bliki/CircuitBreaker.html
 * @see https://resilience4j.readme.io/docs/circuitbreaker
 */
interface ManualCircuitBreaker extends CircuitBreaker
{
    /**
     * A permit for one call, or null when the call is not admitted (circuit
     * open, half-open trials in use, or state unknown under a fail-closed
     * policy). Never throws for a rejection.
     */
    public function tryAcquirePermit(): ?CallPermit;

    /**
     * A permit for one call.
     *
     * @throws CallNotPermittedException when the call is not admitted; its
     *                                   retryAfterSeconds tells when to try again
     */
    public function acquirePermit(): CallPermit;

    /** The admitted call returned normally. */
    public function recordSuccess(CallPermit $permit): void;

    /** The admitted call threw $error; the breaker decides whether it counts. */
    public function recordFailure(CallPermit $permit, Throwable $error): void;

    /**
     * The admitted call did not run: nothing is recorded, and a half-open
     * trial slot held by the permit becomes free again.
     */
    public function releasePermit(CallPermit $permit): void;
}
