<?php

declare(strict_types=1);

namespace KaririCode\Contract\Resilience;

use Throwable;

/**
 * Signals that a resilience guard rejected a call without executing it.
 *
 * Transport layers map it to their vocabulary (HTTP 503 + Retry-After,
 * gRPC UNAVAILABLE, CLI exit code); the guard stays protocol-agnostic
 * (ARFA 1.3 P4).
 *
 * Example: `catch (CallNotPermittedException $e) { $wait = $e->retryAfterSeconds ?? 1; }`
 */
interface CallNotPermittedException extends Throwable
{
    /** Whole seconds until a retry may be admitted; null when unknown. */
    public ?int $retryAfterSeconds { get; }
}
