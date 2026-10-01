<?php

declare(strict_types=1);

namespace KaririCode\Contract\Resilience;

use KaririCode\Contract\Exception\Runtime;

/**
 * Signals that a resilience guard rejected a call without executing it,
 * because the protected dependency is considered unhealthy.
 *
 * Transport layers map it to their vocabulary (HTTP 503 + Retry-After,
 * gRPC UNAVAILABLE, CLI exit code); the guard stays protocol-agnostic
 * (ARFA 1.3 P4). It belongs to the Runtime family (since 2.11.0), so edges
 * that only know the families still answer 503 or 500.
 *
 * Preconditions: retryAfterSeconds is whole seconds, at least 1 when known,
 * null when the guard cannot tell.
 *
 * Thread-safety: an exception belongs to the call that threw it.
 *
 * Side effects: none.
 *
 * Example: `catch (CallNotPermittedException $e) { $wait = $e->retryAfterSeconds ?? 1; }`
 *
 * References: Nygard, Release It! (2nd ed.), ch. 5; RFC 9110 §10.2.3
 * (Retry-After); Spec V4.3.3 §8.4.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-10.2.3
 */
interface CallNotPermittedException extends Runtime
{
    /** Whole seconds until a retry may be admitted; null when unknown. */
    public ?int $retryAfterSeconds { get; }
}
