<?php

declare(strict_types=1);

namespace KaririCode\Contract\Exception;

/**
 * A component was set up with values it cannot work with: a missing
 * setting, an invalid option, a binding that cannot be satisfied.
 *
 * Preconditions: operators fix it, callers cannot. Detect it as early as
 * possible (constructor, provider register()) and fail fast (Spec V4.3.3
 * §8.5); messages must not carry secrets.
 *
 * Thread-safety: an exception belongs to the call that threw it and holds
 * no shared state.
 *
 * Side effects: none.
 *
 * Example: `final class InvalidQuota extends \InvalidArgumentException implements Configuration {}`;
 * the HTTP edge answers 500 and keeps the detail in the logs.
 *
 * References: Spec V4.3.3 §8.4 and §8.5; RFC 9110 §15.6.1.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.6.1
 */
interface Configuration extends ExceptionInterface {}
