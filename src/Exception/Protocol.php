<?php

declare(strict_types=1);

namespace KaririCode\Contract\Exception;

/**
 * Data arrived in a shape its protocol does not allow: malformed JSON, an
 * unknown frame, a header that breaks its grammar.
 *
 * Preconditions: raised by the boundary that parses the wire format, before
 * the data becomes a domain type (Spec V4.3.3 §20.1); input that parses but
 * breaks a domain rule is Validation.
 *
 * Thread-safety: an exception belongs to the call that threw it and holds
 * no shared state.
 *
 * Side effects: none.
 *
 * Example: `final class MalformedFrame extends \UnexpectedValueException implements Protocol {}`;
 * the HTTP edge answers 400.
 *
 * References: Spec V4.3.3 §8.4 and §20.1; RFC 9110 §15.5.1.
 *
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-15.5.1
 */
interface Protocol extends ExceptionInterface {}
