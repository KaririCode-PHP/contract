<?php

declare(strict_types=1);

namespace KaririCode\Contract\Context;

/**
 * Keeps the context of the call being served (tenant, principal, trace)
 * where every component can read it, without threading it through every
 * signature and without leaking it into the next request on runtimes that
 * reuse the process.
 *
 * Preconditions: one slot per type, keyed by the class or interface the
 * writer names; $value is an instance of $type and immutable; store()
 * replaces the slot. find() returns null for an empty slot (E-036). Readers
 * ask for the contract (`Identity::class`), not for an implementation.
 *
 * Thread-safety: implementations keep one context per request, coroutine
 * or fiber (a Swoole coroutine context, a WeakMap keyed by Fiber, plain
 * memory under PHP-FPM); a value stored in one never appears in another.
 *
 * Side effects: store() and clear() change the current context only; the
 * runtime calls clear() when the request ends.
 *
 * Example:
 *
 *     $context->store(Identity::class, $identity);        // authenticator
 *     $identity = $context->find(Identity::class);         // authorization
 *     $context->clear();                                   // runtime, end of request
 *
 * References: KaririCode Spec V4.3.3 §12; OpenTelemetry Specification,
 * "Context".
 *
 * @see https://opentelemetry.io/docs/specs/otel/context/
 */
interface ContextStorage
{
    /**
     * @template T of object
     *
     * @param class-string<T> $type
     * @param T               $value
     */
    public function store(string $type, object $value): void;

    /**
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T|null
     */
    public function find(string $type): ?object;

    public function clear(): void;
}
