<?php

declare(strict_types=1);

namespace KaririCode\Contract\Configuration;

/**
 * Transforms one configuration value while configuration is being loaded.
 *
 * Processors form a chain applied to every scalar value after parsing and
 * before validation. Each one first says whether the value concerns it
 * (supports()) and only then transforms it (process()), so a value without
 * placeholder, envelope or type prefix passes the chain at the cost of one
 * check per processor.
 *
 * Typical processors: `${VAR:default}` interpolation, explicit casts
 * (`int:8080`), decryption of `ENC(...)` envelopes. The shape follows
 * Symfony's environment variable processors, without the container.
 *
 * Example: `$value = $processor->supports($raw) ? $processor->process($raw) : $raw;`
 */
interface ValueProcessor
{
    /**
     * Whether this processor handles the value. Called before process().
     */
    public function supports(mixed $value): bool;

    /**
     * Transformed value; called only when supports() returned true.
     * Returning the input unchanged is valid.
     *
     * @throws \RuntimeException when the value cannot be processed
     */
    public function process(mixed $value): mixed;
}
