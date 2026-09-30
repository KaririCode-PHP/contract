<?php

declare(strict_types=1);

namespace KaririCode\Contract\Resilience;

/**
 * Guards calls to an unreliable dependency and short-circuits them while it
 * is considered unhealthy (circuit breaker pattern).
 *
 * Consumers type against this contract; where the state lives (process
 * memory, Redis) stays an implementation detail of the breaker package.
 *
 * Thread-safety: implementations shared by several processes MUST keep
 * their state in storage with atomic transitions. isOpen() is advisory.
 *
 * Example:
 *
 *     try {
 *         $rates = $breaker->execute(fn (): array => $client->fetchRates());
 *     } catch (CallNotPermittedException $rejected) {
 *         $rates = $fallback->lastKnownRates();
 *     }
 *
 * @see https://martinfowler.com/bliki/CircuitBreaker.html
 */
interface CircuitBreaker
{
    /**
     * Runs the operation when the call is admitted and records its outcome.
     * Exceptions thrown by the operation are rethrown unchanged.
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     *
     * @throws CallNotPermittedException when the call is short-circuited
     */
    public function execute(callable $operation): mixed;

    /**
     * True while calls are being rejected. Advisory: the state may change
     * before the next execute(), which remains authoritative. Remote-backed
     * implementations perform I/O here.
     */
    public function isOpen(): bool;
}
