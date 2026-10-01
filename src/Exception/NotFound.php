<?php

declare(strict_types=1);

namespace KaririCode\Contract\Exception;

/**
 * A lookup by identity found nothing where the caller required a result:
 * get() of an absent entry, a resource or route that does not exist.
 *
 * Preconditions: pair the throwing get() with a find() that returns null
 * for callers that expect absence (E-036). An empty search is an empty
 * collection, never this exception.
 *
 * Thread-safety: an exception belongs to the call that threw it and holds
 * no shared state.
 *
 * Side effects: none.
 *
 * Example: `final class TenantNotFound extends \OutOfBoundsException implements NotFound {}`;
 * the HTTP edge answers 404.
 *
 * References: Spec V4.3.3 §8.4 and §9.3 (E-036); RFC 9110 §15.5.5.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.5.5
 */
interface NotFound extends ExceptionInterface {}
