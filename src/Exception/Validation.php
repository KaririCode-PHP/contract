<?php

declare(strict_types=1);

namespace KaririCode\Contract\Exception;

/**
 * Input was rejected because it breaks a rule the caller controls; the
 * caller can correct the input and try again.
 *
 * Preconditions: throw it for input the caller supplied. A broken setting
 * is Configuration and malformed wire data is Protocol. A component that
 * collects every error returns a result object instead, and the edge throws
 * at most once (Spec V4.3.3 §8.1).
 *
 * Thread-safety: an exception belongs to the call that threw it and holds
 * no shared state.
 *
 * Side effects: none.
 *
 * Example: `final class ValidationFailed extends \DomainException implements Validation {}`;
 * the HTTP edge answers 422.
 *
 * References: Spec V4.3.3 §8.4; RFC 9110 §15.5.21 (422 Unprocessable Content).
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.5.21
 */
interface Validation extends ExceptionInterface {}
