<?php

declare(strict_types=1);

namespace KaririCode\Contract\Exception;

/**
 * An infrastructure fault while running: a store, a peer or a dependency
 * did not answer as expected, and the same call may succeed later.
 *
 * Preconditions: the caller neither causes nor prevents it. A retry policy
 * may retry it; a circuit breaker's rejection (CallNotPermittedException)
 * belongs here.
 *
 * Thread-safety: an exception belongs to the call that threw it and holds
 * no shared state.
 *
 * Side effects: none.
 *
 * Example: `final class StoreUnavailable extends \RuntimeException implements Runtime {}`;
 * the HTTP edge answers 503 with Retry-After when the delay is known, 500
 * otherwise.
 *
 * References: Spec V4.3.3 §8.4; RFC 9110 §15.6.4 (503) and §10.2.3.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.6.4
 */
interface Runtime extends ExceptionInterface {}
