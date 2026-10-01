<?php

declare(strict_types=1);

namespace KaririCode\Contract\Resilience;

/**
 * Answer of a RateLimiter for one key at one instant.
 *
 * Read-only by contract: get-only properties (PHP 8.4) that a readonly
 * class satisfies with plain readonly properties, without hooks (E-013).
 *
 * Preconditions an implementation guarantees: 0 <= remaining <= limit;
 * retryAfter is 0 when allowed and at least 1 when denied; durations are
 * whole seconds, rounded up.
 *
 * Thread-safety: a decision is an immutable snapshot and may be shared
 * freely; it describes the instant it was taken, not the present.
 *
 * Side effects: none; reading a property never consults the limiter.
 *
 * Example:
 *
 *     if (!$decision->allowed) {
 *         $response = $response->withStatus(429)->withHeader('Retry-After', (string) $decision->retryAfter);
 *     }
 *
 * References: RFC 9110 §10.2.3 (Retry-After); RFC 6585 §4 (429).
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-10.2.3
 */
interface RateLimitDecision
{
    public bool $allowed { get; }

    /** Most units the key can hold (bucket capacity or window limit). */
    public int $limit { get; }

    /** Whole units still available after this decision. */
    public int $remaining { get; }

    /** Seconds until the same request can succeed; 0 when allowed. */
    public int $retryAfter { get; }

    /** Seconds until the quota is fully restored. */
    public int $resetAfter { get; }
}
