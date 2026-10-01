<?php

declare(strict_types=1);

namespace KaririCode\Contract;

/**
 * Marks an object whose public readonly properties are its complete and
 * safe serialized form, so it goes to json_encode() as it is; it replaces
 * jsonSerialize() and toArray() (errata E-001).
 *
 * Preconditions: every public property is readonly and holds a scalar, an
 * array of those, a backed enum or another SerializableData. Pure enums,
 * closures and resources make json_encode() fail, and secrets do not belong
 * in public properties: whatever is public gets published.
 *
 * Thread-safety: implementations are immutable value objects.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     final readonly class Money implements SerializableData
 *     {
 *         public function __construct(public int $amount, public string $currency) {}
 *     }
 *
 *     json_encode(new Money(1250, 'BRL'), JSON_THROW_ON_ERROR); // {"amount":1250,"currency":"BRL"}
 *
 * References: KaririCode Spec V4.3.3 §9 and errata E-001.
 *
 * @see https://www.php.net/manual/en/function.json-encode.php
 */
interface SerializableData {}
